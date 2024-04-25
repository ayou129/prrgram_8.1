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
use App\Model\SysRequest;
use App\Model\SysRequestSql;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;

/**
 * @AutoController
 */
class RequestController extends AbstractController
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
                    'email',
                    'like',
                    '%' . $params['blurry'] . '%',
                ],
            ];
        }

        $models = SysRequest::where($where)
            ->where(function ($query) use ($whereOr) {
                foreach ($whereOr as $item) {
                    $query->where(...$item[0])
                        ->orWhere(...$item[1]);
                }
            })
            ->with([
                'sqls',
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

    public function delete()
    {
        $idsArray = $this->request->all();
        if (! $idsArray || ! is_array($idsArray)) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据有误');
        }

        Db::beginTransaction();
        try {
            $model = SysRequest::where('id', '=', $idsArray[0])
                ->first();
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR);
            }

            # 判断所有依赖关系 TODO
            $model->delete();

            # 删除相关表
            SysRequestSql::where('request_id', $model->id)
                ->delete();

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
