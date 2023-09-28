<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Controller\V1\Business\Wuliu\Chewu;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use App\Model\WuliuCar;
use App\Model\WuliuMotorcade;
use Hyperf\DbConnection\Db;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class MotorcadeController extends AbstractController
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
        $typeArray = WuliuMotorcade::getTypeArray();
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
        $models = (new WuliuMotorcade());
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
        //     Motorcade::TYPE_JINKOU,
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

    public function all() {}

    public function post()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            # 关联数据

            # 检查是否重复
            $model = WuliuMotorcade::where('name', $params['name'])
                ->first();
            if ($model) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '存在相同数据：' . $params['name']);
            }
            $model = new WuliuMotorcade();
            $model->name = $params['name'];
            $model->type = $params['type'];
            $model->base_salary = $params['base_salary'];
            $model->save();

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (\Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function put()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            // 查看数据是否存在
            $model = WuliuMotorcade::find($params['id']);
            if (! $model) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '数据不存在');
            }

            // 检查是否存在相同数据
            $existsCount = WuliuMotorcade::where('name', $params['name'])->where('id', '<>', $params['id'])->count();
            if ($existsCount) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '存在相同数据：' . $params['name']);
            }

            $model->name = $params['name'];
            $model->type = $params['type'];
            $model->base_salary = $params['base_salary'];
            $model->save();

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (\Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function delete()
    {
        // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '不允许执行');
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            $params = array_unique($params);
            $models = WuliuMotorcade::whereIn('id', $params)->get();
            if (! $models->count()) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '需要删除的数据为空');
            }
            if ($models->count() != count($params)) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '部分数据不存在，请刷新页面重试');
            }

            // 关联1
            $relationModelCount = WuliuCar::whereIn('motorcade_id', $params)->count();
            if ($relationModelCount) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '车辆已绑定该数据，请先处理车辆数据后再次进行操作');
            }

            // // 关联2
            // $relationModelCount = Relation::where('', '')->count();
            // if ($relationModelCount) {
            //     throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR);
            // }

            WuliuMotorcade::whereIn('id', $params)->delete();

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (\Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function deleteSingle()
    {
        throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '不允许执行');
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            $model = WuliuMotorcade::where('', '')->find($params['id']);
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
        } catch (\Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
