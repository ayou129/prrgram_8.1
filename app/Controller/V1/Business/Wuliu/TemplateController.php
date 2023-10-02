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

namespace App\Controller\V1\Business\Wuliu;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\HttpMessage\Exception\HttpException;

class TemplateController extends AbstractController
{
    public function searchOptions()
    {
        // ->pluck('name')
        // $shipCompanyModels = WuliuShipCompany::orderBy('created_at', 'desc')->with(['sailSchedule'])->get();
        // $sailScheduleModels = WuliuSailSchedule::orderBy('created_at', 'desc')->get();
        // $carModels = WuliuCar::orderBy('created_at', 'desc')->get();
        // $billModels = WuliuBill::orderBy('created_at', 'desc')->get();
        // $needReceiptArray = WuliuSeaWaybill::getReceiptStatusArray();
        // $needPoundbillArray = WuliuSeaWaybill::getPoundbillStatusArray();
        $typeArray = WuliuSeaWaybill::getTypeArray();
        // $seaWaybillModels = WuliuSeaWaybill::select(['id', 'number', 'case_number'])->get();
        // $seaWaybillResult = [];
        // foreach ($seaWaybillModels as $value) {
        //     $exists = false;
        //     foreach ($seaWaybillResult as $k => $val) {
        //         if ($value->number === $val['number']) {
        //             $exists = true;
        //             break;
        //         }
        //     }
        //     if (! $exists) {
        //         $seaWaybillResult[] = $value->toArray();
        //     }
        // }
        // $seaWaybillNumbers = $seaWaybillModels->pluck('','name')
        // $carsArray = $carModels->toArray();
        $result = [
            // 'ship_company' => $shipCompanyModels->toArray(),
            // 'sail_schedule' => $sailScheduleModels->toArray(),
            // 'car' => $carsArray,
            // 'paiche' => $carsArray,
            // 'bill' => $billModels->toArray(),
            // 'receipt_status_array' => $needReceiptArray,
            // 'poundbill_status_array' => $needPoundbillArray,
            'type_array' => $typeArray,
        ];
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function list()
    {
        $params = $this->getRequestAllFilter();
        $models = (new Template());
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
        //     Template::TYPE_JINKOU,
        // ];

        $models = $models->where($where)
            ->where(function ($query) use ($whereOr) {
                foreach ($whereOr as $item) {
                    $query->where(...$item[0])
                        ->orWhere(...$item[1]);
                }
            })
            ->with([
            ])->orderBy('id', 'desc');

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
        $params['dict_id'] = $params['dict']['id'];

        Db::beginTransaction();
        try {
            # 关联数据

            # 检查是否重复
            $model = Template::where('name', $params['name'])
                ->first();
            if ($model) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '存在相同数据：' . $params['name']);
            }
            $model = new Template();
            $model->value = $params['value'];
            $model->create_by = $params['create_by'] ?? 'admin';
            $model->update_by = $params['update_by'] ?? 'admin';
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
        // var_dump($params);
        $params['dict_id'] = $params['dict']['id'];

        Db::beginTransaction();
        try {
            // 查看数据是否存在
            $model = Template::find($params['id']);
            if (! $model) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '数据不存在');
            }

            // 检查是否存在相同数据
            $existsCount = Template::where('name', $params['name'])->count();
            if ($existsCount) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '存在相同数据：' . $params['name']);
            }

            $model->value = $params['value'];
            $model->create_by = $params['create_by'] ?? 'admin';
            $model->update_by = $params['update_by'] ?? 'admin';
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
            $models = Template::whereIn('id', $params)->get();
            if (! $models->count()) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '需要删除的数据为空');
            }
            if ($models->count() != count($params)) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '部分数据不存在，请刷新页面重试');
            }

            // 关联1
            $relationModelCount = Relation::where('', '')->count();
            if ($relationModelCount) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR);
            }

            // 关联2
            $relationModelCount = Relation::where('', '')->count();
            if ($relationModelCount) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR);
            }

            Template::whereIn('id', $params)->delete();

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function deleteSingle()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            $model = Template::where('', '')->find($params['id']);
            if (! $model) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '数据不存在');
            }

            // 关联1
            $relationModelCount = Relation::where('', '')->count();
            if ($relationModelCount) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR);
            }

            // 关联2
            $relationModelCount = Relation::where('', '')->count();
            if ($relationModelCount) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR);
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
