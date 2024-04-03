<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace App\Controller\V1\Admin;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use App\Exception\RetException;
use App\Exception\ServiceException;
use App\Model\SysDept;
use App\Model\SysRole;
use App\Model\SysUser;
use App\Service\Admin\AdminService;
use App\Utils\Tools;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class AdminController extends AbstractController
{
    #[Inject]
    public AdminService $adminService;

    public function authPermcode()
    {
        return $this->responseJson(ServiceCode::SUCCESS, [
            '1000',
            '3000',
            '5000',
        ]);
    }

    public function authUserMenus()
    {
        $token = $this->request->header('Authorization');
        if (! isset($token)) {
            throw new ServiceException(ServiceCode::ERROR, [], 200, [], '缺少token');
        }

        $sysUserModel = SysUser::where('token', $token)
            ->with([
                'role' => function ($query) {
                    return $query->with([
                        'menus',
                    ]);
                },
            ])
            ->first();
        // var_dump($token,$sysUserModel);
        if (! $sysUserModel->role || ! $sysUserModel->role->menus) {
            throw new ServiceException(ServiceCode::ERROR, [], 200, [], '用户无权限');
        }

        $menus = $sysUserModel->role->menus->toArray();
        foreach ($menus as $key => &$value) {
            $value['meta'] = [
                // 'hideChildrenInMenu' => true,
                'icon' => $value['icon'],
                'title' => $value['title'],
            ];
            unset($value['icon'], $value['title']);
        }
        $menus = Tools::reorganizeDepartments($menus);

        return $this->responseJson(ServiceCode::SUCCESS, $menus);
    }

    public function authLogin()
    {
        $params = $this->getRequestAllFilter();

        if (! isset($params['username'], $params['password'])) {
            throw new ServiceException(ServiceCode::ERROR, [], 200, [], '请输入账号和密码');
        }
        $sysUserModel = SysUser::where('username', $params['username'])
            ->with([
                // 'authorities',
                'roles' => function ($query) {
                    return $query->with(['menus']);
                },
                'dept',
                'jobs',
            ])
            // ->select([
            //     'id',
            //     'username',
            //     'is_admin',
            // ])
            ->first();
        if (! $sysUserModel) {
            throw new ServiceException(ServiceCode::ERROR, [], 200, [], '用户不存在');
        }

        if ($sysUserModel->password != $params['password']) {
            throw new ServiceException(ServiceCode::ERROR, [], 200, [], '密码错误');
        }
        # 该权限所有允许的menu
        $roles_permissions = [];
        $authorities = [];
        if ($sysUserModel->is_admin == SysUser::IS_ADMIN) {
            $authorities[]['authority'] = 'admin';
            $roles_permissions[] = 'admin';
        } else {
            $sysUserModel->roles->map(function ($rule) use (&$roles_permissions) {
                $rule->menus->map(function ($menu) use (&$roles_permissions) {
                    if ($menu->permission) {
                        $roles_permissions[] = $menu->permission;
                    }
                });
            });
            foreach ($roles_permissions as $roles_permission) {
                $authorities[]['authority'] = $roles_permission;
            }
        }

        $token = Tools::generateRandomPassword();

        $result = [
            'token' => $token,
            'user' => [],
        ];

        $result['user']['roles'] = array_unique($roles_permissions);
        $result['user']['authorities'] = $authorities;
        $result['user']['dataScopes'] = [];
        $result['user']['user'] = $sysUserModel->toArray();

        $sysUserModel->token = $token;
        $sysUserModel->token_expiretime = AdminService::getRefreshTokenExpiretime();
        $sysUserModel->save();

        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function authUserInfo()
    {
        $token = $this->request->header('Authorization');
        if (! isset($token)) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '缺少token');
        }
        $sysUserModel = SysUser::where('token', $token)
            ->with([
                'dept',
                'jobs',
            ])
            ->first();
        // var_dump($token,$sysUserModel);
        if (! $sysUserModel) {
            throw new ServiceException(ServiceCode::ERROR_USER_IS_NOT_ADMIN);
        }
        $result = [
            'token' => $token,
            'user' => [],
        ];

        $result = array_merge($result, $sysUserModel->toArray());
        // $result['] = $sysUserModel->toArray();
        // $sysUserModel->token = $token;
        // $sysUserModel->save();

        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function authLogout()
    {
        return $this->responseJson(ServiceCode::SUCCESS, []);
    }

    public function userList()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $limit = (int) $this->request->input('page_limit', 10);
        $models = (new SysUser())->with(['role', 'dept']);

        # 搜索条件
        if (isset($params['username']) && $params['username']) {
            $models = $models->where('username', 'like', "%{$params['username']}%");
        }
        if (isset($params['nick_name']) && $params['nick_name']) {
            $models = $models->where('nick_name', 'like', "%{$params['nick_name']}%");
        }
        if (isset($params['dept_id']) && $params['dept_id']) {
            $models = $models->where('dept_id', $params['dept_id']);
        }
        if (isset($params['role_id']) && $params['role_id']) {
            $models = $models->where('role_id', $params['role_id']);
        }

        $result = $models->paginate($limit);

        $result = $result->toArray();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function postUser()
    {
        $params = $this->getRequestAllFilter();

        Db::beginTransaction();
        try {
            $exists = SysUser::where('username', '=', $params['username'])
                ->count();
            if ($exists) {
                throw new RetException('data already exists');
            }

            if (! isset($params['dept_id'], $params['role_id'])) {
                throw new RetException('has not dept_id or role_id');
            }

            $deptModel = SysDept::find($params['dept_id']);
            if (! $deptModel) {
                throw new RetException('dept data not exists');
            }

            $roleModel = SysRole::find($params['role_id']);
            if (! $roleModel) {
                throw new RetException('role data not exists');
            }

            $model = (new SysUser());
            $model->username = $params['username'];
            $model->password = $params['password'] ?? '123456';
            $model->nick_name = $params['nick_name'] ?? '';
            $model->email = $params['email'] ?? '';
            $model->phone = $params['phone'] ?? '';
            $model->dept_id = $params['dept_id'] ?? 0;
            $model->role_id = $params['role_id'] ?? 0;
            $model->status = $params['status'];
            $model->save();

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function putUser()
    {
        $params = $this->getRequestAllFilter();

        Db::beginTransaction();
        try {
            $model = SysUser::find($params['id']);
            if (! $model) {
                throw new RetException('data not exists');
            }
            $change = false;

            if (isset($params['username'])) {
                $count = SysUser::where('username', '=', $params['username'])
                    ->where('id', '!=', $params['id'])
                    ->count();
                if ($count) {
                    var_dump($count);
                    throw new RetException('username already exists');
                }
                $model->username = $params['username'];
                $change = true;
            }

            if (isset($params['dept_id'])) {
                $deptModel = SysDept::find($params['dept_id']);
                if (! $deptModel) {
                    throw new RetException('dept data not exists');
                }

                $model->dept_id = $params['dept_id'];
                $change = true;
            }
            if (isset($params['role_id'])) {
                $roleModel = SysRole::find($params['role_id']);
                if (! $roleModel) {
                    throw new RetException('role data not exists');
                }

                $model->role_id = $params['role_id'];
                $change = true;
            }

            if (isset($params['password'])) {
                $model->password = $params['password'];
                $change = true;
            }

            if (isset($params['nick_name'])) {
                $model->nick_name = $params['nick_name'];
                $change = true;
            }
            if (isset($params['email'])) {
                $model->email = $params['email'];
                $change = true;
            }
            if (isset($params['phone'])) {
                $model->phone = $params['phone'];
                $change = true;
            }
            if (isset($params['status'])) {
                $model->status = $params['status'];
                $change = true;
            }

            if ($change) {
                $model->save();
                Db::commit();
            }

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function deleteUser()
    {
        $params = $this->getRequestAllFilter();

        Db::beginTransaction();
        try {
            $model = SysUser::find($params['id']);
            if (! $model) {
                throw new RetException('not found');
            }

            $model->delete();

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
