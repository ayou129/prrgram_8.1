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
use App\Model\SysRequestLog;

class RequestLogController extends AbstractController
{
    public function list()
    {
        $models = (new SysRequestLog());
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
        // if (isset($params['blurry'])) {
        //     $whereOr[] = [
        //         [
        //             'username',
        //             'like',
        //             '%' . $params['blurry'] . '%',
        //         ],
        //         [
        //             'email',
        //             'like',
        //             '%' . $params['blurry'] . '%',
        //         ],
        //     ];
        // }

        $models = $models->where($where)
            ->where(function ($query) use ($whereOr) {
                foreach ($whereOr as $item) {
                    $query->where(...$item[0])
                        ->orWhere(...$item[1]);
                }
            })
            ->with([
                // 'roles',
            ]);

        $result = $models->paginate((int) $this->request->input('size', 10));
        $result = $result->toArray();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function all()
    {
    }

    public function create()
    {
    }

    public function put()
    {
    }

    public function delete()
    {
    }
}
