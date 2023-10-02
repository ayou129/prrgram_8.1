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

class DictDetailController extends AbstractController
{
    public function list()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $limit = (int) $this->request->input('size', 10);
        $dictName = $this->request->input('dictName');
        $models = SysDictDetail::with([
            'dict',
        ]);

        $params['sort'] = $this->request->input('sort') ?? [];
        foreach ($params['sort'] as $item) {
            $sort = explode(',', $item);
            $sort_field = $sort[0];
            $sort_type = $sort[1];
            $models = $models->orderBy($sort_field, $sort_type);
        }
        if (isset($dictName)) {
            $sysDictModels = SysDict::where('name', $dictName)
                ->pluck('id');
            $sysDictIds = $sysDictModels->isEmpty() ? [] : $sysDictModels->toArray();
            $models = $models->whereIn('dict_id', $sysDictIds);
        }

        // if (isset($params['created_at_start_time'])) {
        //     $sysDeptModel = $sysDeptModel->where('created_at', '>=', $params['created_at_start_time']);
        // }
        // if (isset($params['created_at_end_time'])) {
        //     $sysDeptModel = $sysDeptModel->where('created_at', '<=', $params['created_at_end_time']);
        // }
        $result = $models->paginate($limit);
        $result = $result->toArray();
        // BaseModel::addTreeFields($result['data']);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function create()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $params['dict_id'] = $params['dict']['id'];

        $dictModel = SysDict::find($params['dict_id']);
        if (! $dictModel) {
            throw new ServiceException(ServiceCode::ERROR);
        }

        $model = (new SysDictDetail());
        $model->dict_id = $params['dict_id'];
        $model->label = $params['label'];
        $model->value = $params['value'];
        $model->dict_sort = $params['dict_sort'];
        $model->create_by = $params['create_by'] ?? 'admin';
        $model->update_by = $params['update_by'] ?? 'admin';
        $model->save();

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function put()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $params['dict_id'] = $params['dict']['id'];

        $dictModel = SysDict::find($params['dict_id']);
        if (! $dictModel) {
            throw new ServiceException(ServiceCode::ERROR);
        }

        $model = SysDictDetail::find($params['id']);
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR);
        }

        $model->dict_id = $params['dict_id'];
        $model->label = $params['label'];
        $model->value = $params['value'];
        $model->dict_sort = $params['dict_sort'];
        $model->create_by = $params['create_by'] ?? 'admin';
        $model->update_by = $params['update_by'] ?? 'admin';
        $model->save();

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function delete()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        $model = SysDictDetail::find($params['id'][0]);
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR);
        }

        $model->delete();

        return $this->responseJson(ServiceCode::SUCCESS);
    }
}
