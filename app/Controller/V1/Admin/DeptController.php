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
use App\Model\SysDept;
use App\Utils\Tools;

class DeptController extends AbstractController
{
    public function list()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $limit = (int) $this->request->input('page_limit', 10);

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
        if (! isset($params['pid'])) {
            $params['pid'] = 0;
        }
        $where[] = [
            'pid',
            $params['pid'],
        ];

        // var_dump($where);
        # status filter
        if (isset($params['status'])) {
            $params['status'] = $params['status'] === 'true' ? '1' : '0';
            $where[] = [
                'status',
                $params['status'],
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

        // if (! isset($params['pid'])) {
        //     $params['pid'] = 0;
        // }
        // $where[] = [
        //     'pid',
        //     $params['pid'],
        // ];
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
        $result = Tools::reorganizeDepartments($result, 0);
        // BaseModel::addTreeFields($result);
        // SysDept::addLabelField($result);

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
        $model->status = $params['status'];
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
        $model = SysDept::find($params['id']);
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR_DEPT_NOT_EXISTS);
        }

        $model->pid = $params['pid'];
        $model->name = $params['name'];
        $model->dept_sort = $params['dept_sort'];
        $model->status = $params['status'];
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
