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
use App\Model\BaseModel;
use App\Model\SysMenu;
use App\Model\SysRole;
use App\Model\SysRolesDept;
use App\Model\SysRolesMenu as SysRolesMenus;
use App\Model\SysUser;

class RoleController extends AbstractController
{
    public function getById(int $id)
    {
        // var_dump($id);
        $models = SysRole::with([
            'menus',
            'depts',
        ])
            ->find($id);
        $result = $models ? [] : $models->toArray();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function list()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $limit = (int) $this->request->input('size', 10);

        $models = (new SysRole());
        $params['sort'] = $this->request->input('sort') ?? [];
        foreach ($params['sort'] as $item) {
            $sort = explode(',', $item);
            $sort_field = $sort[0];
            $sort_type = $sort[1];
            $models = $models->orderBy($sort_field, $sort_type);
        }

        $where = [];

        if (isset($params['created_at_start_time'])) {
            $where[] = [
                'created_at',
                '>=',
                $params['created_at_start_time'],
            ];
        }
        if (isset($params['created_at_end_time'])) {
            $where[] = [
                'created_at',
                '<=',
                $params['created_at_end_time'],
            ];
        }
        if (isset($params['blurry'])) {
            $where[] = [
                'name',
                'like',
                '%' . $params['blurry'] . '%',
            ];
        }

        $models = $models->where($where)
            ->with([
                'menus',
                'depts',
            ])
            ->paginate($limit);

        $result = $models->isEmpty() ? [] : $models->toArray();
        foreach ($result['data'] as &$item) {
            if (isset($item['menus'])) {
                SysMenu::addLabelField($item['menus']);
                BaseModel::addTreeFields($item['menus']);
            }
        }
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function create()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        $model = (new SysRole());
        $model->name = $params['name'] ?? '';
        $model->level = $params['level'];
        $model->description = $params['description'];
        $model->data_scope = $params['data_scope'];
        $model->create_by = $params['create_by'] ?? 'admin';
        $model->update_by = $params['update_by'] ?? 'admin';
        $model->save();
        if (isset($params['depts']) && is_array($params['depts'])) {
            $ids = [];
            foreach ($params['depts'] as $item) {
                $ids[] = $item['id'];
            }
            $ids = array_unique($ids);
            $prepareSaveData = [];
            foreach ($ids as $id) {
                $prepareSaveData[] = [
                    'role_id' => $model->id,
                    'dept_id' => $id,
                ];
            }
            SysRolesDept::insert($prepareSaveData);
        }
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function put()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        # Java code
        // Role dict = dictRepository.findById(resources.getId()).orElseGet(Role::new);
        // ValidationUtil.isNull( dict.getId(),"Role","id",resources.getId());

        # 验证is_frame http https
        // $model = (new SysRole())->where('id', '=', $params['id'])
        //     ->first();
        $model = SysRole::query()
            ->find($params['id']);
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR_DEPT_NOT_EXISTS);
        }

        if (isset($params['depts']) && is_array($params['depts'])) {
            $ids = [];
            foreach ($params['depts'] as $item) {
                $ids[] = $item['id'];
            }
            $ids = array_unique($ids);
            $prepareSaveData = [];
            foreach ($ids as $id) {
                $prepareSaveData[] = [
                    'role_id' => $model->id,
                    'dept_id' => $id,
                ];
            }
            // var_dump($prepareSaveData);
            SysRolesDept::query()
                ->where('role_id', $model->id)
                ->delete();
            SysRolesDept::insert($prepareSaveData);
        }

        $model->name = $params['name'] ?? '';
        $model->level = $params['level'];
        $model->description = $params['description'];
        $model->data_scope = $params['data_scope'];
        $model->create_by = $params['create_by'] ?? 'admin';
        $model->update_by = $params['update_by'] ?? 'admin';
        $model->save();

        // var_dump($model->toArray());
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function putMenu()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        # Java code
        // Role dict = dictRepository.findById(resources.getId()).orElseGet(Role::new);
        // ValidationUtil.isNull( dict.getId(),"Role","id",resources.getId());

        # 验证is_frame http https
        // $model = (new SysRole())->where('id', '=', $params['id'])
        //     ->first();
        $model = SysRole::query()
            ->find($params['id']);
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR_DEPT_NOT_EXISTS);
        }

        if (isset($params['menus']) && is_array($params['menus'])) {
            $ids = [];
            foreach ($params['menus'] as $item) {
                $ids[] = $item['id'];
            }
            $ids = array_unique($ids);
            $prepareSaveData = [];
            foreach ($ids as $id) {
                $prepareSaveData[] = [
                    'role_id' => $model->id,
                    'menu_id' => $id,
                ];
            }
            // var_dump($prepareSaveData);
            SysRolesMenus::query()
                ->where('role_id', $model->id)
                ->delete();
            SysRolesMenus::insert($prepareSaveData);
        }
        // var_dump($model->toArray());
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function delete()
    {
        $idsArray = $this->request->all();
        if (! $idsArray || ! is_array($idsArray)) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据有误');
        }
        // var_dump($idsArray,'$idsArray');
        // $childMenus = SysRole::whereIn('pid', $idsArray)
        //     ->pluck('id');
        // $childMenuIds = $childMenus->isEmpty() ? [] : $childMenus->toArray();
        // var_dump($childMenus,'$childMenus');
        $needDeleteIdsArray = array_merge($idsArray, []);

        $needDeleteIdsArray = array_unique($needDeleteIdsArray);
        // var_dump($needDeleteIdsArray,'$needDeleteIdsArray');
        if ($needDeleteIdsArray) {
            SysRole::whereIn('id', $needDeleteIdsArray)
                ->delete();
        }
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function level()
    {
        $token = $this->request->header('Authorization');
        if (! isset($token)) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '请携带token');
        }
        $sysUserModel = SysUser::where('token', $token)
            ->with([
                'roles' => function ($query) {},
            ])
            ->first();
        if (! $sysUserModel) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据不存在');
        }
        $min_level = null;
        foreach ($sysUserModel->roles as $role) {
            if ($min_level === null) {
                $min_level = $role->level;
            } else {
                if ($min_level > $role->level) {
                    $min_level = $role->level;
                }
            }
        }
        // $params = $this->getRequestAllFilter();
        // $model = SysRole::query()
        //     ->select('level')
        //     ->find($params['id']);
        // if (! $model) {
        //     throw new Serv/iceException(ServiceCode::ERROR_DEPT_NOT_EXISTS);
        // }
        return $this->responseJson(ServiceCode::SUCCESS, ['level' => $min_level]);
    }

    public function getLevels($val = null)
    {
        // if (level != null) {
        //     if (level < min) {
        //         throw new BadRequestException("权限不足，你的角色级别：" + min + "，低于操作的角色级别：" + level);
        //     }
        // }
    }

    // 返回全部的角色
    public function all()
    {
        $models = SysRole::with([
            'menus',
        ])
            ->get();
        $result = $models->isEmpty() ? [] : $models->toArray();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }
}
