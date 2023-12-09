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

namespace App\Controller\V1\Business\Wuliu\Bill;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use App\Exception\ServiceException;
use App\Model\WuliuBill;
use App\Model\WuliuSeaWaybill;
use App\Service\Business\Wuliu\Bill\BillService;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class BillController extends AbstractController
{
    #[Inject]
    private BillService $billService;

    public function optons()
    {
        $types = WuliuBill::getTypeArray();
        // $typesArray = [];

        // foreach ($$types as $key => $value) {
        //     $typesArray[] = [
        //         'id' => $key,
        //         'value' => $key,
        //     ];
        // }
        // $typesArray = [];
        $result = [
            'search' => [],
            'types' => $types,
        ];
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function list()
    {
        $params = $this->getRequestAllFilter();
        $models = (new WuliuBill());
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
        //     $where[] = [
        //         'key',
        //         'like',
        //         '%' . $params['blurry'] . '%',
        //     ];
        // }
        // $where[] = [
        //     'type',
        //     '=',
        //     WuliuBill::TYPE_MOTORCADE,
        // ];

        $models = $models->where($where)
            ->where(function ($query) use ($whereOr) {
                foreach ($whereOr as $item) {
                    $query->where(...$item[0])
                        ->orWhere(...$item[1]);
                }
            })
            ->with([
            ])
            ->orderBy('id', 'desc');

        $result = $models->paginate((int) $this->request->input('size', 10));
        $result = $result->toArray();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function all()
    {
    }

    public function post()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        // $params['dict_id'] = $params['dict']['id'];

        Db::beginTransaction();
        try {
            # 关联数据

            # 检查是否重复
            $model = WuliuBill::where('title', $params['title'])
                ->where('type', $params['type'])
                ->first();
            if ($model) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '存在相同数据：' . $params['title'] . $params['type']);
            }
            $model = new WuliuBill();
            $model->title = $params['title'];
            $model->type = $params['type'];
            $model->save();

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function put()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        // $params['dict_id'] = $params['dict']['id'];

        Db::beginTransaction();
        try {
            // 查看数据是否存在
            $model = WuliuBill::find($params['id']);
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据不存在');
            }

            // 检查是否存在相同数据
            $existsCount = WuliuBill::where('title', $params['title'])
                ->where('type', $params['type'])
                ->count();

            if ($existsCount) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '存在相同数据：' . $params['title']);
            }

            $model->title = $params['title'];
            $model->type = $params['type'];
            $model->save();

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function putStatus()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        // $params['dict_id'] = $params['dict']['id'];

        Db::beginTransaction();
        try {
            // 查看数据是否存在
            $model = WuliuBill::find($params['id']);
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据不存在');
            }
            switch ($params['status']) {
                case '确认账单':
                    $status = WuliuBill::STATUS_CONFIRMED;
                    break;
                case '取消确认':
                    $status = WuliuBill::STATUS_DEFAULT;
                    break;
                case '确认已付':
                    $status = WuliuBill::STATUS_PAID;
                    break;
                case '取消已付':
                    $status = WuliuBill::STATUS_CONFIRMED;
                    break;
                default:
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '状态有误');
            }

            $model->status = $status;
            $model->save();

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
        // var_dump($params);

        Db::beginTransaction();
        try {
            $params = array_unique($params);
            $models = WuliuBill::whereIn('id', $params)
                ->get();
            if (! $models->count()) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '需要删除的数据为空');
            }
            if ($models->count() != count($params)) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '部分数据不存在，请刷新页面重试');
            }

            // 关联1
            // var_dump($params);
            $relationModelCount = WuliuSeaWaybill::whereIn('ship_company_bill_id', $params)
                ->orWhereIn('motorcade_bill_id', $params)
                ->orWhereIn('partner_bill_id', $params)
                ->orWhereIn('self_bill_id', $params)
                ->count();
            if ($relationModelCount) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '该数据下的海运单存在关联的数据，无法删除');
            }

            // 关联2
            // $relationModelCount = Relation::where('', '')->count();
            // if ($relationModelCount) {
            //     throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR);
            // }

            WuliuBill::whereIn('id', $params)
                ->delete();

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function deleteSingle()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            $model = WuliuBill::where('', '')
                ->find($params['id']);
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据不存在');
            }

            // 关联1
            $relationModelCount = Relation::where('', '')
                ->count();
            if ($relationModelCount) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, []);
            }

            // 关联2
            $relationModelCount = Relation::where('', '')
                ->count();
            if ($relationModelCount) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, []);
            }

            $model->delete();

            Db::commit();
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function export()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            $model = WuliuBill::find($params['id']);
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据不存在');
            }
            // 查询出所有 海运单，计算总额，导出
            $result = $this->billService->export($model);
            // var_dump($result);
            Db::commit();
            // ob_clean();
            // return $this->response->download($result['path']);
            return $this->response->download($result['path'], $result['filename']);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
