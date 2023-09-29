<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Controller\V1\Admin;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use App\Exception\ServiceException;
use App\Model\BaseModel;
use App\Model\SysDept;
use App\Model\SysUser;
use Hyperf\HttpServer\Annotation\AutoController;


class DeptController extends AbstractController
{
    public function list()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $limit = (int) $this->request->input('size', 10);

        $models = (new SysDept());
        $params['sort'] = $this->request->input('sort') ?? [];
        foreach ($params['sort'] as $item) {
            $sort = explode(',', $item);
            $sort_field = $sort[0];
            $sort_type = $sort[1];
            $models = $models->orderBy($sort_field, $sort_type);
        }
        $where = [];

        # pid filter
        if (! isset($params['pid']) || $params['pid'] == 0) {
            $params['pid'] = null;
        }
        $where[] = [
            'pid',
            $params['pid'],
        ];

        // var_dump($where);
        # enabled filter
        if (isset($params['enabled'])) {
            $params['enabled'] = $params['enabled'] === 'true' ? '1' : '0';
            $where[] = [
                'enabled',
                $params['enabled'],
            ];
        }
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

        $result = $models->where($where)
            ->paginate($limit);

        $result = $result->toArray();
        BaseModel::addTreeFields($result['data']);
        SysDept::addLabelField($result['data']);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function all()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $where = [];

        /**
         * 根据用户所有的角色 查出 date_scope
         * 1.有且只有一个 date_scope 并 '全部'   无pid则返回 的所有数据      有pid则返回 ；pid=$params['pid'] 的所有数据
         * 1.有且只有一个 date_scope 并 '本级'   无pid则返回 自己部门数据    有pid则返回 ；[]
         * 1.有且只有一个 date_scope 并 '自定义'  无pid则返回 自己部门       有pid则返回 自己部门
         * 2.有多个 date_scope 并包含 '全部' 返回 最小的
         * 3.有多个 date_scope 并不包含 '全部' 返回 所有的.
         */
        # 获取当前用户的权限级别
        // $token = $this->request->header('Authorization');
        // if (! isset($token)) {
        //     return $this->responseJson(ServiceCode::ERROR_PARAM_MISSING);
        // }
        // $sysUserModel = SysUser::where('token', $token)
        //     ->with([
        //         'roles' => function ($query) {
        //             return $query->orderBy('level', 'asc')->limit(1);
        //         },
        //         'dept' => function ($query) {
        //         },
        //         // 'jobs'
        //     ])
        //     ->first();
        // $role_data_scope_level = '';
        // // var_dump($sysUserModel->toArray());
        // if (! $sysUserModel->roles->isEmpty()) {
        //     foreach ($sysUserModel->roles as $item) {
        //         // var_dump($item->data_scope);
        //         if ($item->data_scope === '全部') {
        //             $role_data_scope_level = $item->data_scope;
        //             break;
        //         } elseif ($item->data_scope === '本级') {
        //             $role_data_scope_level = $item->data_scope;
        //             break;
        //         } elseif ($item->data_scope === '自定义') {
        //             $role_data_scope_level = $item->data_scope;
        //             break;
        //         }
        //     }
        // }
        // var_dump($role_data_scope_level,'$role_data_scope_level');

        if (! isset($params['pid']) || $params['pid'] == 0) {
            $params['pid'] = null;
        }
        $where[] = [
            'pid',
            $params['pid'],
        ];
        $models = SysDept::where($where);
        $params['sort'] = $this->request->input('sort') ?? [];
        foreach ($params['sort'] as $item) {
            $sort = explode(',', $item);
            $sort_field = $sort[0];
            $sort_type = $sort[1];
            $models = $models->orderBy($sort_field, $sort_type);
        }

        $models = $models->get();

        $result = $models->isEmpty() ? [] : $models->toArray();
        BaseModel::addTreeFields($result);
        SysDept::addLabelField($result);

        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    /**
     * 查询菜单:根据ID获取同级与上级数据.
     */
    public function superior()
    {
        $menuIdsArray = $this->request->all();
        if ($menuIdsArray) {
            // if (false) {
            // var_dump($menuIdsArray, '$menuIdsArray');
            # in menu_id ids
            # 根据ID获取同级与上级数据
            $models = SysDept::whereIn('id', $menuIdsArray)
                // ->orderBy($sort_field, $sort_type)
                ->get();
            $result = $models->toArray();
            SysDept::addTreeFields($result);
            SysDept::addLabelField($result);
            // $a = [];
            // $menusData = $menusModels->toArray();
            // foreach ($menusData as $item) {
            //     if($item['pid'] === null){
            //         $a[] = $item;
            //     }else{
            //
            //     }
            // }
            //
            // $result = self::buildTree($menusData);
            // return $this->responseJson(ServiceCode::SUCCESS, $result);
            //
            // foreach ($menusModels as $item) {
            //
            // }
            // pid=null的话 orderBy menu_sort
            // 每个菜单
            return $this->responseJson(ServiceCode::SUCCESS, $result);
        }
        # 所有顶级菜单
        $models = SysDept::where('pid', '=', null)
            // ->orderBy($sort_field, $sort_type)
            ->get();

        $result = $models->toArray();
        SysDept::addTreeFields($result);
        SysDept::addLabelField($result);

        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function create()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        $model = (new SysDept());
        $model->pid = $params['pid'];
        $model->sub_count = $params['sub_count'] ?? 0;
        $model->name = $params['name'] ?? '';
        $model->dept_sort = $params['dept_sort'];
        $model->enabled = $params['enabled'];
        $model->create_by = $params['create_by'] ?? 'admin';
        $model->update_by = $params['update_by'] ?? 'admin';
        $model->save();

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function put()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        if ($params['id'] === $params['pid']) {
            throw new ServiceException(ServiceCode::ERROR_DEPT_PID_ID_EQUALS);
        }

        # Java code
        // Dept dept = deptRepository.findById(resources.getId()).orElseGet(Dept::new);
        // ValidationUtil.isNull( dept.getId(),"Dept","id",resources.getId());

        # 验证is_frame http https
        // $model = (new SysDept())->where('id', '=', $params['id'])
        //     ->first();
        $model = SysDept::query()
            ->find($params['id']);
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR_DEPT_NOT_EXISTS);
        }

        $model->pid = $params['pid'];
        $model->sub_count = $params['sub_count'];
        $model->name = $params['name'];
        $model->dept_sort = $params['dept_sort'];
        $model->enabled = $params['enabled'];
        $model->create_by = $params['create_by'] ?? 'admin';
        $model->update_by = $params['update_by'] ?? 'admin';
        $model->save();

        // var_dump($model->toArray());
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function delete()
    {
        $idsArray = $this->request->all();
        if (! $idsArray || ! is_array($idsArray)) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_FORMAT);
        }
        // var_dump($idsArray,'$idsArray');
        $childMenus = SysDept::whereIn('pid', $idsArray)
            ->pluck('id');
        $childMenuIds = $childMenus->isEmpty() ? [] : $childMenus->toArray();
        // var_dump($childMenus,'$childMenus');
        $needDeleteIdsArray = array_merge($idsArray, $childMenuIds);

        $needDeleteIdsArray = array_unique($needDeleteIdsArray);
        // var_dump($needDeleteIdsArray,'$needDeleteIdsArray');
        if ($needDeleteIdsArray) {
            SysDept::whereIn('id', $needDeleteIdsArray)
                ->delete();
        }
        return $this->responseJson(ServiceCode::SUCCESS);
    }
}
