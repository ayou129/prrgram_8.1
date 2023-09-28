<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Controller\V1\Business\Wuliu\Chewu;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use App\Model\WuliuBill;
use App\Model\WuliuDriver;
use App\Model\WuliuSeaWaybill;
use Hyperf\DbConnection\Db;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpServer\Annotation\AutoController;

#[AutoController]
class DriverController extends AbstractController
{
    public function list()
    {
        $params = $this->getRequestAllFilter();
        $models = (new WuliuDriver());
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
        //     WuliuDriver::TYPE_JINKOU,
        // ];

        $models = $models->where($where)
            ->where(function ($query) use ($whereOr) {
                foreach ($whereOr as $item) {
                    $query->where(...$item[0])
                        ->orWhere(...$item[1]);
                }
            })
            ->with([
            ])->orderBy('id', 'asc');

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
            # 检查是否重复
            $model = WuliuDriver::where('name', $params['name'])
                ->first();
            if ($model) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '存在相同的合作方:' . $params['name']);
            }
            $model = new WuliuDriver();
            $model->name = $params['name'];
            $model->save();
            Db::commit();
        } catch (\Exception $e) {
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
            $model = WuliuDriver::find($params['id']);
            if (! $model) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '数据不存在');
            }

            // 检查是否存在相同数据
            $existsCount = WuliuDriver::where('name', $params['name'])->count();
            if ($existsCount) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '存在相同数据：' . $params['name']);
            }

            $model->name = $params['name'];
            $model->save();

            Db::commit();
        } catch (\Throwable $ex) {
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
            $models = WuliuDriver::whereIn('id', $params)->get();
            if (! $models->count()) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '需要删除的数据为空');
            }
            if ($models->count() != count($params)) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '部分数据不存在，请刷新页面重试');
            }

            // // 关联1：账单表
            // $relationBillModelCount = WuliuBill::where('', '')->count();
            // if ($relationBillModelCount) {
            //     throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '该合作方存在绑定的 账单数据，无法删除');
            // }

            // // 关联2：海运单表
            // $relatioSeaWaybillModelCount = WuliuSeaWaybill::where('', '')->count();
            // if ($relatioSeaWaybillModelCount) {
            //     throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '该合作方存在绑定的 海运单数据，无法删除');
            // }

            WuliuDriver::whereIn('id', $params)->delete();

            Db::commit();
        } catch (\Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }

        return $this->responseJson(ServiceCode::SUCCESS);
    }
}
