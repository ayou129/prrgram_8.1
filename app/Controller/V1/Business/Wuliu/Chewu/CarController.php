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
use App\Model\WuliuBill;
use App\Model\WuliuCar;
use App\Model\WuliuMotorcade;
use App\Model\WuliuSeaWaybill;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\HttpMessage\Exception\HttpException;
use Throwable;

class CarController extends AbstractController
{
    public function searchOptions()
    {
        $motorcadeTypeArray = WuliuMotorcade::getTypeArray();
        $motorcadeArray = WuliuMotorcade::get();
        $result = [
            'motorcade_type_array' => $motorcadeTypeArray,
            'motorcade_array' => $motorcadeArray,
        ];
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function list()
    {
        $params = $this->getRequestAllFilter();
        $models = (new WuliuCar());
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
        if (isset($params['number'])) {
            $where[] = [
                'number',
                'like',
                '%' . $params['number'] . '%',
            ];
        }
        // $where[] = [
        //     'type',
        //     '=',
        //     WuliuCar::TYPE_JINKOU,
        // ];

        $models = $models->where($where)
            ->where(function ($query) use ($whereOr) {
                foreach ($whereOr as $item) {
                    $query->where(...$item[0])
                        ->orWhere(...$item[1]);
                }
            })
            ->with([
                'motorcade',
            ])->orderBy('motorcade_id', 'asc')->orderBy('id', 'asc');

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

        Db::beginTransaction();
        try {
            # 检查是否重复
            $model = WuliuCar::where('number', $params['number'])->where('motorcade_id', $params['motorcade_id'])
                ->first();
            if ($model) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '存在相同的数据:' . $params['number'] . $params['motorcade_id']);
            }
            $motorcadeModel = WuliuMotorcade::where('id', $params['motorcade_id'])
                ->first();
            if (! $motorcadeModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '车队数据不存在:' . $params['motorcade_id']);
            }

            $model = new WuliuCar();
            $model->number = $params['number'];
            $model->motorcade_id = $params['motorcade_id'];
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

        Db::beginTransaction();
        try {
            // 查看数据是否存在
            $model = WuliuCar::find($params['id']);
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据不存在');
            }

            // 检查是否存在相同数据
            $existsCount = WuliuCar::where('number', $params['number'])->where('motorcade_id', $params['motorcade_id'])->count();
            if ($existsCount) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '存在相同数据：' . $params['number'] . '-' . $params['motorcade_id']);
            }

            $model->number = $params['number'];
            $model->motorcade_id = $params['motorcade_id'];
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
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            $params = array_unique($params);
            unset($params['token']);

            $models = WuliuCar::whereIn('id', $params)->get();
            if (! $models->count()) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '需要删除的数据为空');
            }
            if ($models->count() != count($params)) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '部分数据不存在，请刷新页面重试');
            }

            // // 关联1：账单表
            // $relationBillModelCount = WuliuBill::where('', '')->count();
            // if ($relationBillModelCount) {
            //     throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '该合作方存在绑定的 账单数据，无法删除');
            // }

            // 关联2：海运单表
            $relatioSeaWaybillModelCount = WuliuSeaWaybill::whereIn('car_id', $params)->count();
            if ($relatioSeaWaybillModelCount) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '该车辆存在绑定的 海运单数据，无法删除');
            }

            WuliuCar::whereIn('id', $params)->delete();

            Db::commit();
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }

        return $this->responseJson(ServiceCode::SUCCESS);
    }
}
