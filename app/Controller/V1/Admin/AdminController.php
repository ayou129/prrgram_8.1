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
use App\Exception\ServiceException;
use App\Model\SysUser;
use App\Service\Admin\AdminService;
use App\Utils\Tools;
use Hyperf\Di\Annotation\Inject;

class AdminController extends AbstractController
{
    #[Inject]
    public AdminService $adminService;

    public function authLogin()
    {
        $params = $this->getRequestAllFilter();

        if (! isset($params['username'], $params['password'])) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '请输入账号和密码');
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
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '用户不存在');
        }

        if ($sysUserModel->password != $params['password']) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '密码错误');
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

    public function authInfo()
    {
        $token = $this->request->header('Authorization');
        if (! isset($token)) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '缺少token');
        }
        $sysUserModel = SysUser::where('token', $token)
            ->with([
                'roles' => function ($query) {
                    return $query->with([
                        'menus',
                    ]);
                },
                'dept',
                'jobs',
            ])
            ->first();
        // var_dump($token,$sysUserModel);
        if (! $sysUserModel) {
            throw new ServiceException(ServiceCode::ERROR_USER_IS_NOT_ADMIN);
        }
        // if ( $sysUserModel->is_admin != SysUser::IS_ADMIN) {
        //     throw new ServiceException(ServiceCode::ERROR_ADMIN_IS_NOT_ADMIN_FAIL);
        // }
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

        $result = [
            'token' => $token,
            'user' => [],
        ];

        $result['roles'] = array_unique($roles_permissions);
        $result['authorities'] = $authorities;
        $result['dataScopes'] = [];
        $result['user'] = $sysUserModel->toArray();
        // $sysUserModel->token = $token;
        // $sysUserModel->save();

        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function authLogout()
    {
        return $this->responseJson(ServiceCode::SUCCESS, []);
    }

    public function users()
    {
    }

    public function menus()
    {
        return 'menus';
    }

    public function getInfo()
    {
        return 1;
    }

    // public function list()
    // {
    //     return $this->responseJson(ServiceCode::SUCCESS, $this->adminService->list($this->request->all()));
    // }
}
