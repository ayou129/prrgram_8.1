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
use App\Model\SysMenu;
use App\Model\SysRole;
use App\Model\SysRolesMenu as SysRolesMenus;
use App\Model\SysUser;
use Exception;
use Hyperf\DbConnection\Db;

class RoleController extends AbstractController
{
    public function all()
    {
        $models = SysRole::with([
            'menus',
        ])
            ->get();
        $result = $models->isEmpty() ? [] : $models->toArray();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function list()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $limit = (int) $this->request->input('page_limit', 10);

        $models = (new SysRole());
        // 循环查询条件
        foreach ($params as $key => $value) {
            if ($key == 'name') {
                $models = $models->where('name', 'like', '%' . $value . '%');
            }
            if ($key == 'value') {
                $models = $models->where('value', $value);
            }
            if ($key == 'status') {
                $models = $models->where('status', $value);
            }
        }

        $models = $models->with(['menus'])->paginate($limit);
        $result = $models->toArray();
        foreach ($result['data'] as &$value) {
            $menus = [];
            foreach ($value['menus'] as $key => $menu) {
                $menus[] = $menu['id'];
            }
            $value['menu_ids'] = $menus;
        }
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function post()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            $model = SysRole::where('name', $params['name'])->first();
            if ($model) {
                throw new RetException('data already exists');
            }

            $model = new SysRole();
            $model->name = $params['name'] ?? '';
            $model->value = $params['value'] ?? '';
            $model->status = $params['status'] ?? 0;
            $model->sort = $params['sort'] ?? 100;
            $model->remark = $params['remark'] ?? '';
            $model->save();

            if (isset($params['menu_ids'])) {
                if (! is_array($params['menu_ids'])) {
                    throw new RetException('menu_ids field error');
                }

                $ids = array_unique($params['menu_ids']);
                $menuModels = SysMenu::findMany($params['menu_ids']);
                if ($menuModels->count() != count($params['menu_ids'])) {
                    throw new RetException('menu_ids not found');
                }

                $prepareSaveData = [];
                foreach ($ids as $id) {
                    $prepareSaveData[] = [
                        'role_id' => $model->id,
                        'menu_id' => $id,
                    ];
                }
                SysRolesMenus::where('role_id', $model->id)
                    ->delete();
                SysRolesMenus::insert($prepareSaveData);
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function put()
    {
        $params = $this->getRequestAllFilter();

        Db::beginTransaction();
        try {
            $model = SysRole::find($params['id']);
            if (! $model) {
                throw new RetException('Role not found');
            }

            $change = false;
            if (isset($params['name'])) {
                $model->name = $params['name'];
                $change = true;
            }

            if (isset($params['value'])) {
                $model->value = $params['value'];
                $change = true;
            }

            if (isset($params['status'])) {
                $model->status = $params['status'];
                $change = true;
            }

            if (isset($params['sort'])) {
                $model->sort = $params['sort'];
                $change = true;
            }

            if (isset($params['remark'])) {
                $model->remark = $params['remark'];
                $change = true;
            }

            if (isset($params['menu_ids'])) {
                if (! is_array($params['menu_ids'])) {
                    throw new RetException('menu_ids field error');
                }

                $ids = array_unique($params['menu_ids']);
                $menuModels = SysMenu::findMany($params['menu_ids']);
                if ($menuModels->count() != count($params['menu_ids'])) {
                    throw new RetException('menu_ids not found');
                }

                $prepareSaveData = [];
                foreach ($ids as $id) {
                    $prepareSaveData[] = [
                        'role_id' => $model->id,
                        'menu_id' => $id,
                    ];
                }
                SysRolesMenus::where('role_id', $model->id)
                    ->delete();
                SysRolesMenus::insert($prepareSaveData);

                $change = true;
            }

            if ($change) {
                $model->save();
            }
            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function delete()
    {
        $params = $this->getRequestAllFilter();

        Db::beginTransaction();
        try {
            $model = SysRole::with(['user'])->find($params['id']);
            if (! $model) {
                throw new RetException('Role not found');
            }
            if ($model->user) {
                throw new RetException('Role has user');
            }

            SysRolesMenus::where('role_id', $model->id)
                ->delete();

            $model->delete();

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    // public function level()
    // {
    //     $token = $this->request->header('Authorization');
    //     if (! isset($token)) {
    //         throw new ServiceException(ServiceCode::ERROR, [], 400, [], '请携带token');
    //     }
    //     $sysUserModel = SysUser::where('token', $token)
    //         ->with([
    //             'roles' => function ($query) {},
    //         ])
    //         ->first();
    //     if (! $sysUserModel) {
    //         throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据不存在');
    //     }
    //     $min_level = null;
    //     foreach ($sysUserModel->roles as $role) {
    //         if ($min_level === null) {
    //             $min_level = $role->level;
    //         } else {
    //             if ($min_level > $role->level) {
    //                 $min_level = $role->level;
    //             }
    //         }
    //     }
    //     // $params = $this->getRequestAllFilter();
    //     // $model = SysRole::query()
    //     //     ->select('level')
    //     //     ->find($params['id']);
    //     // if (! $model) {
    //     //     throw new Serv/iceException(ServiceCode::ERROR_DEPT_NOT_EXISTS);
    //     // }
    //     return $this->responseJson(ServiceCode::SUCCESS, ['level' => $min_level]);
    // }

    // public function putMenu()
    // {
    //     $params = $this->getRequestAllFilter();
    //     // var_dump($params);

    //     Db::beginTransaction();
    //     try {
    //         $model = SysRole::find($params['id']);
    //         if (! $model) {
    //             throw new ServiceException(ServiceCode::ERROR_DEPT_NOT_EXISTS);
    //         }

    //         if (isset($params['menus']) && is_array($params['menus'])) {
    //             $ids = [];
    //             foreach ($params['menus'] as $item) {
    //                 $ids[] = $item['id'];
    //             }
    //             $ids = array_unique($ids);
    //             $prepareSaveData = [];
    //             foreach ($ids as $id) {
    //                 $prepareSaveData[] = [
    //                     'role_id' => $model->id,
    //                     'menu_id' => $id,
    //                 ];
    //             }
    //             // var_dump($prepareSaveData);
    //             SysRolesMenus::query()
    //                 ->where('role_id', $model->id)
    //                 ->delete();
    //             SysRolesMenus::insert($prepareSaveData);
    //         }
    //         Db::commit();
    //         return $this->responseJson(ServiceCode::SUCCESS);
    //     } catch (Exception $e) {
    //         Db::rollBack();
    //         throw $e;
    //     }

    //         // var_dump($model->toArray());
    // }
}
