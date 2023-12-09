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

namespace App\Controller\V1\Business\Wuliu\Chewu;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use App\Exception\ServiceException;
use App\Model\WuliuShipCompany;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\HttpMessage\Exception\HttpException;
use Throwable;

class ShipCompanyController extends AbstractController
{
    public function list()
    {
        $params = $this->getRequestAllFilter();
        $models = (new WuliuShipCompany());
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
        // $where[] = [
        //     'type',
        //     '=',
        //     WuliuShipCompany::TYPE_JINKOU,
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
        $models = WuliuShipCompany::get();
        return $this->responseJson(ServiceCode::SUCCESS, $models->toArray());
    }

    public function post()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        // $params['dict_id'] = $params['dict']['id'];

        # 关联数据

        # 检查是否重复
        $model = WuliuShipCompany::where('name', $params['name'])
            ->first();
        if ($model) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '存在相同数据：' . $params['name']);
        }

        $model = new WuliuShipCompany();
        $model->name = $params['name'];
        $model->save();

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
            $model = WuliuShipCompany::find($params['id']);
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据不存在');
            }

            // 检查是否存在相同数据
            $existsCount = WuliuShipCompany::where('name', $params['name'])
                ->count();
            if ($existsCount) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '存在相同数据：' . $params['name']);
            }

            $model->name = $params['name'];
            $model->save();

            Db::commit();
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function delete()
    {
        // $params = $this->getRequestAllFilter();
        // var_dump($params);
        throw new ServiceException(ServiceCode::ERROR, [], 400, [], '当前船公司不允许删除');
        // Db::beginTransaction();
        // try {
        //     $params = array_unique($params);
        //     $models = WuliuShipCompany::whereIn('id', $params)->get();
        //     if (! $models->count()) {
        //         throw new ServiceException(ServiceCode::ERROR, [], 400, [], '需要删除的数据为空');
        //     }
        //     if ($models->count() != count($params)) {
        //         throw new ServiceException(ServiceCode::ERROR, [], 400, [], '部分数据不存在，请刷新页面重试');
        //     }
        //
        //     // // 关联1：账单表
        //     // $relationBillModelCount = WuliuBill::where('', '')->count();
        //     // if ($relationBillModelCount) {
        //     //     throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '该合作方存在绑定的 账单数据，无法删除');
        //     // }
        //
        //     // // 关联2：海运单表
        //     // $relatioSeaWaybillModelCount = WuliuSeaWaybill::where('', '')->count();
        //     // if ($relatioSeaWaybillModelCount) {
        //     //     throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '该合作方存在绑定的 海运单数据，无法删除');
        //     // }
        //
        //     WuliuShipCompany::whereIn('id', $params)->delete();
        //
        //     Db::commit();
        // } catch (Exception $e) {
        //     Db::rollBack();
        //     throw $e;
        // }

        // return $this->responseJson(ServiceCode::SUCCESS);
    }
}
