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
use App\Model\SysJob;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class JobController extends AbstractController
{
    public function list()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $limit = (int) $this->request->input('size', 10);

        $models = (new SysJob());
        $params['sort'] = $this->request->input('sort') ?? [];
        foreach ($params['sort'] as $item) {
            $sort = explode(',', $item);
            $sort_field = $sort[0];
            $sort_type = $sort[1];
            $models = $models->orderBy($sort_field, $sort_type);
        }

        $where = [];

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
        if (isset($params['name'])) {
            $where[] = [
                'name',
                'like',
                '%' . $params['name'] . '%',
            ];
        }
        $result = $models->where($where)
            ->paginate($limit);

        $result = $result->toArray();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function create()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        # 不能重复 name
        $exists = SysJob::where('name', '=', $params['name'])
            ->count();
        if ($exists) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }

        $model = (new SysJob());
        $model->name = $params['name'];
        $model->enabled = $params['enabled'];
        $model->job_sort = $params['job_sort'] ?? 0;
        $model->create_by = $params['create_by'] ?? 'admin';
        $model->update_by = $params['update_by'] ?? 'admin';
        $model->save();

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function put()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        # 验证 name
        $exists = SysJob::where('name', '=', $params['name'])
            ->where('id', '<>', $params['id'])
            ->count();
        if ($exists) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }

        $model = SysJob::query()
            ->find($params['id']);
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_IS_NOT_EXISTS_ERROR);
        }

        $model->name = $params['name'];
        $model->enabled = $params['enabled'];
        $model->job_sort = $params['job_sort'] ?? 0;
        $model->create_by = $params['create_by'] ?? 'admin';
        $model->update_by = $params['update_by'] ?? 'admin';
        $model->save();
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function delete()
    {
        $idsArray = $this->request->all();
        if (! $idsArray || ! is_array($idsArray)) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_FORMAT);
        }
        // var_dump($idsArray,'$idsArray');
        // $childMenus = SysJob::whereIn('pid', $idsArray)
        //     ->pluck('id');
        // $childMenuIds = $childMenus->isEmpty() ? [] : $childMenus->toArray();
        // var_dump($childMenus,'$childMenus');
        $needDeleteIdsArray = array_merge($idsArray, []);

        $needDeleteIdsArray = array_unique($needDeleteIdsArray);
        // var_dump($needDeleteIdsArray,'$needDeleteIdsArray');
        if ($needDeleteIdsArray) {
            SysJob::whereIn('id', $needDeleteIdsArray)
                ->delete();
        }
        return $this->responseJson(ServiceCode::SUCCESS);
    }
}
