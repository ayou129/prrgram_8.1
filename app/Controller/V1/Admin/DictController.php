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
use App\Model\SysDict;
use App\Model\SysDictDetail;
use App\Model\SysUser;

class DictController extends AbstractController
{
    public function list()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $limit = (int) $this->request->input('page_limit', 10);
        $where = $whereOr = [];

        if (isset($params['blurry'])) {
            $whereOr[] = [
                [
                    'username',
                    'like',
                    '%' . $params['blurry'] . '%',
                ],
                [
                    'email',
                    'like',
                    '%' . $params['blurry'] . '%',
                ],
            ];
        }

        $models = SysDict::where($where)
            ->where(function ($query) use ($whereOr) {
                foreach ($whereOr as $item) {
                    $query->where(...$item[0])
                        ->orWhere(...$item[1]);
                }
            })
            ->with([
                'details',
            ]);

        $params['sort'] = $this->request->input('sort') ?? [];
        foreach ($params['sort'] as $item) {
            $sort = explode(',', $item);
            $sort_field = $sort[0];
            $sort_type = $sort[1];
            $models = $models->orderBy($sort_field, $sort_type);
        }

        $result = $models->paginate($limit);

        $result = $result->toArray();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    // public function all()
    // {
    //     $params = $this->getRequestAllFilter();
    //     // var_dump($params);
    //     $sort = explode(',', $this->request->input('sort', 'id,asc'));
    //     $sort_field = $sort[0];
    //     $sort_type = $sort[1];
    //     $where = [];
    //
    //     /**
    //      * 根据用户所有的角色 查出 date_scope
    //      * 1.有且只有一个 date_scope 并 '全部'   无pid则返回 的所有数据      有pid则返回 ；pid=$params['pid'] 的所有数据
    //      * 1.有且只有一个 date_scope 并 '本级'   无pid则返回 自己部门数据    有pid则返回 ；[]
    //      * 1.有且只有一个 date_scope 并 '自定义'  无pid则返回 自己部门       有pid则返回 自己部门
    //      * 2.有多个 date_scope 并包含 '全部' 返回 最小的
    //      * 3.有多个 date_scope 并不包含 '全部' 返回 所有的
    //      */
    //     # 获取当前用户的权限级别
    //     // $token = $this->request->header('Authorization');
    //     // if (! isset($token)) {
    //     //     return $this->responseJson(ServiceCode::ERROR_PARAM_MISSING);
    //     // }
    //     // $sysUserModel = SysUser::where('token', $token)
    //     //     ->with([
    //     //         'roles' => function ($query) {
    //     //             return $query->orderBy('level', 'asc')->limit(1);
    //     //         },
    //     //         'dept' => function ($query) {
    //     //         },
    //     //         // 'jobs'
    //     //     ])
    //     //     ->first();
    //     // $role_data_scope_level = '';
    //     // // var_dump($sysUserModel->toArray());
    //     // if (! $sysUserModel->roles->isEmpty()) {
    //     //     foreach ($sysUserModel->roles as $item) {
    //     //         // var_dump($item->data_scope);
    //     //         if ($item->data_scope === '全部') {
    //     //             $role_data_scope_level = $item->data_scope;
    //     //             break;
    //     //         } elseif ($item->data_scope === '本级') {
    //     //             $role_data_scope_level = $item->data_scope;
    //     //             break;
    //     //         } elseif ($item->data_scope === '自定义') {
    //     //             $role_data_scope_level = $item->data_scope;
    //     //             break;
    //     //         }
    //     //     }
    //     // }
    //     // var_dump($role_data_scope_level,'$role_data_scope_level');
    //
    //     if (! isset($params['pid']) || $params['pid'] == 0) {
    //         $params['pid'] = null;
    //     }
    //     $where[] = [
    //         'pid',
    //         $params['pid'],
    //     ];
    //
    //     $models = SysDict::where($where)
    //         ->orderBy($sort_field, $sort_type)
    //         ->get();
    //
    //     $result = $models->isEmpty() ? [] : $models->toArray();
    //     BaseModel::addTreeFields($result);
    //     SysDict::addLabelField($result);
    //
    //     return $this->responseJson(ServiceCode::SUCCESS, $result);
    // }

    /**
     * 查询菜单:根据ID获取同级与上级数据.
     */
    // public function superior()
    // {
    //     $menuIdsArray = $this->request->all();
    //     if ($menuIdsArray) {
    //         // if (false) {
    //         var_dump($menuIdsArray, '$menuIdsArray');
    //         # in menu_id ids
    //         # 根据ID获取同级与上级数据
    //         $models = SysDict::whereIn('id', $menuIdsArray)
    //             // ->orderBy($sort_field, $sort_type)
    //             ->get();
    //         $result = $models->toArray();
    //         SysDict::addTreeFields($result);
    //         SysDict::addLabelField($result);
    //         // $a = [];
    //         // $menusData = $menusModels->toArray();
    //         // foreach ($menusData as $item) {
    //         //     if($item['pid'] === null){
    //         //         $a[] = $item;
    //         //     }else{
    //         //
    //         //     }
    //         // }
    //         //
    //         // $result = self::buildTree($menusData);
    //         // return $this->responseJson(ServiceCode::SUCCESS, $result);
    //         //
    //         // foreach ($menusModels as $item) {
    //         //
    //         // }
    //         //pid=null的话 orderBy menu_sort
    //         // 每个菜单
    //         return $this->responseJson(ServiceCode::SUCCESS, $result);
    //     } else {
    //         # 所有顶级菜单
    //         $models = SysDict::where('pid', '=', null)
    //             // ->orderBy($sort_field, $sort_type)
    //             ->get();
    //
    //         $result = $models->toArray();
    //         SysDict::addTreeFields($result);
    //         SysDict::addLabelField($result);
    //     }
    //     return $this->responseJson(ServiceCode::SUCCESS, $result);
    // }

    public function create()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        $model = (new SysDict());
        $model->name = $params['name'];
        $model->description = $params['description'];
        $model->create_by = $params['create_by'] ?? 'admin';
        $model->update_by = $params['update_by'] ?? 'admin';
        $model->save();

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function put()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        // $model = (new SysDict())->where('id', '=', $params['id'])
        //     ->first();
        $model = SysDict::query()
            ->find($params['id']);
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR_DEPT_NOT_EXISTS);
        }

        $model->name = $params['name'];
        $model->description = $params['description'];
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
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据有误');
        }

        $model = SysDict::where('id', '=', $idsArray[0])
            ->first();
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR);
        }

        # 判断所有依赖关系 TODO

        # 删除相关表
        SysDictDetail::where('dict_id', $model->id)
            ->delete();
        $model->delete();

        return $this->responseJson(ServiceCode::SUCCESS);
    }
}
