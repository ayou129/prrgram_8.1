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
use App\Model\SysDict;
use App\Model\SysDictDetail;

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
