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
use App\Model\SysDept;
use App\Utils\Tools;
use Exception;
use Hyperf\DbConnection\Db;

class DeptController extends AbstractController
{
    public function list()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $limit = (int) $this->request->input('page_limit', 10);

        $models = (new SysDept());
        // 循环查询条件
        foreach ($params as $key => $value) {
            if ($key == 'name') {
                $models = $models->where('name', 'like', '%' . $value . '%');
            }
            if ($key == 'sort') {
                $models = $models->where('sort', $value);
            }
            if ($key == 'pid') {
                $models = $models->where('pid', $value);
            }
            if ($key == 'status') {
                $models = $models->where('status', $value);
            }
        }

        $result = $models->paginate($limit);

        return $this->responseJson(ServiceCode::SUCCESS, $result->toArray());
    }

    public function all()
    {
        $params = $this->getRequestAllFilter();
        $where = [];

        $models = SysDept::where($where)->get();

        $result = $models->isEmpty() ? [] : $models->toArray();
        $result = Tools::reorganizeDepartments($result, 0);

        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function post()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            $model = SysDept::where('name', $params['name'])->first();
            if ($model) {
                throw new RetException('data already exists');
            }

            $model = new SysDept();
            $model->pid = $params['pid'] ?? '';
            $model->status = $params['status'] ?? 0;
            $model->sort = $params['sort'] ?? 100;
            $model->remark = $params['remark'] ?? '';
            $model->save();

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
            $model = SysDept::find($params['id']);
            if (! $model) {
                throw new RetException(' not found');
            }

            $change = false;
            if (isset($params['pid'])) {
                $model->pid = $params['pid'];
                $change = true;
            }

            if (isset($params['name'])) {
                $model->name = $params['name'];
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
            $model = SysDept::with(['user'])->find($params['id']);
            if (! $model) {
                throw new RetException('not found');
            }
            if ($model->users || ! $model->users->isEmpty()) {
                throw new RetException('has user');
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
