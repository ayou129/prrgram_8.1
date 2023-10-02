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
use App\Model\SysConfig;

class ConfigController extends AbstractController
{
    public function list()
    {
        $params = $this->getRequestAllFilter();
        $models = (new SysConfig());
        $params['sort'] = $this->request->input('sort') ?? [];
        foreach ($params['sort'] as $item) {
            $sort = explode(',', $item);
            $sort_field = $sort[0];
            $sort_type = $sort[1];
            $models = $models->orderBy($sort_field, $sort_type);
        }

        $where = $whereOr = [];
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
                'key',
                'like',
                '%' . $params['blurry'] . '%',
            ];
        }

        $models = $models->where($where)
            ->where(function ($query) use ($whereOr) {
                foreach ($whereOr as $item) {
                    $query->where(...$item[0])
                        ->orWhere(...$item[1]);
                }
            })
            ->with([
            ])
            ->orderBy('key');

        $result = $models->paginate((int) $this->request->input('size', 10));
        $result = $result->toArray();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function all()
    {
    }

    public function create()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        # 关联数据

        # 检查是否重复
        $model = SysConfig::where('key', $params['key'])
            ->first();
        if ($model) {
            throw new ServiceException(ServiceCode::ERROR);
        }
        $model = new SysConfig();
        $model->key = $params['key'];
        $model->value = $params['value'];
        $model->desc = $params['desc'];
        $model->save();

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function put()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        // $params['dict_id'] = $params['dict']['id'];

        $model = SysConfig::find($params['id']);
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR);
        }

        $model->key = $params['key'];
        $model->value = $params['value'];
        $model->desc = $params['desc'];
        $model->save();

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function delete()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        SysConfig::whereIn('id', $params)
            ->delete();

        return $this->responseJson(ServiceCode::SUCCESS);
    }
}
