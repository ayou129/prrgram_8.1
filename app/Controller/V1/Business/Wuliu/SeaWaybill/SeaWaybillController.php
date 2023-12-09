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

namespace App\Controller\V1\Business\Wuliu\SeaWaybill;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use App\Exception\ServiceException;
use App\Model\WuliuBill;
use App\Model\WuliuCar;
use App\Model\WuliuPartner;
use App\Model\WuliuSailSchedule;
use App\Model\WuliuSeaWaybill;
use App\Model\WuliuShipCompany;
use App\Service\Business\Wuliu\SeaWaybill\SeaWaybillService;
use App\Utils\Tools;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Exception\HttpException;
use LogicException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderExcetpion;

class SeaWaybillController extends AbstractController
{
    #[Inject]
    public SeaWaybillService $seaWaybillService;

    public function searchOptions()
    {
        // $shipCompanyModels = WuliuShipCompany::orderBy('created_at', 'desc')->with(['sailSchedule' => function ($q) {
        //     $q->orderBy('arrival_date', 'desc');
        // }])->get();
        $sailScheduleModels = WuliuSailSchedule::orderBy('arrival_date', 'desc')
            ->with(['shipCompany'])
            ->get();
        $carModels = WuliuCar::orderBy('created_at', 'desc')
            ->get();
        $billModels = WuliuBill::orderBy('created_at', 'desc')
            ->get();
        $partnerModels = WuliuPartner::orderBy('created_at', 'desc')
            ->get();
        $needReceiptArray = WuliuSeaWaybill::getReceiptStatusArray();
        $needPoundbillArray = WuliuSeaWaybill::getPoundbillStatusArray();
        $boxReportingStatusArray = WuliuSeaWaybill::getBoxReportingStatusArray();
        $typeArray = WuliuSeaWaybill::getTypeArray();
        $tosArray = WuliuSeaWaybill::getTosArray();
        $fhStatusArray = WuliuSeaWaybill::getFhStatusArray();
        $carsArray = $carModels->toArray();
        $result = [
            // 'ship_company' => $shipCompanyModels->toArray(),
            'sail_schedule' => $sailScheduleModels->toArray(),
            'car' => $carsArray,
            'paiche' => $carsArray,
            'bill' => $billModels->toArray(),
            'receipt_status_array' => $needReceiptArray,
            'poundbill_status_array' => $needPoundbillArray,
            'box_reporting_status_array' => $boxReportingStatusArray,
            'type_array' => $typeArray,
            'tos_array' => $tosArray,
            'fh_status_array' => $fhStatusArray,
            'partners' => $partnerModels->toArray(),
        ];
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function list()
    {
        $params = $this->getRequestAllFilter();
        $models = (new WuliuSeaWaybill());
        $params['sort'] = $this->request->input('sort') ?? [];
        foreach ($params['sort'] as $item) {
            $sort = explode(',', $item);
            $sort_field = $sort[0];
            $sort_type = $sort[1];
            $models = $models->orderBy($sort_field, $sort_type);
        }

        $where = $whereOr = [];
        if (isset($params['type'])) {
            $where[] = [
                'type',
                $params['type'],
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
        if (isset($params['car_finished_date']) && is_array($params['car_finished_date'])) {
            $where[] = [
                'car_finished_date',
                '>=',
                $params['car_finished_date'][0],
            ];
            $where[] = [
                'car_finished_date',
                '<=',
                $params['car_finished_date'][1],
            ];
        }
        if (isset($params['blurry'])) {
            $where[] = [
                'key',
                'like',
                '%' . $params['blurry'] . '%',
            ];
        }
        if (isset($params['number'])) {
            $where[] = [
                'number',
                'like',
                '%' . $params['number'] . '%',
            ];
        }
        if (isset($params['case_number'])) {
            $where[] = [
                'case_number',
                'like',
                '%' . $params['case_number'] . '%',
            ];
        }
        if (isset($params['partner_ids'])) {
            $models = $models->whereIn(
                'partner_id',
                $params['partner_ids']
            );
        }
        if (isset($params['bill_id'])) {
            $models = $models->where(function ($query) use ($params) {
                $query->whereIn(
                    'ship_company_bill_id',
                    $params['bill_id']
                )
                    ->orWhereIn(
                        'partner_bill_id',
                        $params['bill_id'],
                    )
                    ->orWhereIn(
                        'self_bill_id',
                        $params['bill_id'],
                    )
                    ->orWhereIn(
                        'motorcade_bill_id',
                        $params['bill_id'],
                    );
            });
        }
        // if (isset($params['ship_company_ids']) && $params['ship_company_ids']) {
        //     $models = $models->whereIn(
        //         'ship_company_id',
        //         $params['ship_company_ids']
        //     );
        // }
        if (isset($params['car_id_is_null']) && $params['car_id_is_null'] == 'true') {
            $models = $models->where(
                'car_id',
                null
            );
        }
        if (isset($params['dirty_data'])) {
            if ($params['dirty_data'] == 'false') {
                $models = $models->where(
                    'created_at',
                    '>=',
                    '2023-02-01'
                );
            }
        } else {
            $models = $models->where(
                'created_at',
                '>=',
                '2023-02-01'
            );
        }
        if (isset($params['sail_schedule_is_null']) && $params['sail_schedule_is_null'] == 'true') {
            $models = $models->where(
                'sail_schedule_id',
                null
            );
        }
        if (isset($params['sail_schedule_ids']) && $params['sail_schedule_ids']) {
            $models = $models->whereIn(
                'sail_schedule_id',
                $params['sail_schedule_ids']
            );
        }
        if (isset($params['car_ids']) && $params['car_ids']) {
            $models = $models->whereIn(
                'car_id',
                $params['car_ids']
            );
        }
        // $where[] = [
        //     'type',
        //     '=',
        //     WuliuSeaWaybill::TYPE_JINKOU,
        // ];

        $models = $models->where($where)
            ->where(function ($query) use ($whereOr) {
                foreach ($whereOr as $item) {
                    $query->where(...$item[0])
                        ->orWhere(...$item[1]);
                }
            })
            ->with([
                'sailSchedule' => function ($q) {
                    $q->with(['shipCompany']);
                },
                'car',
                'partner',
                'shipCompanyBill',
                'motorcadeBill',
                'partnerBill',
                'selfBill',
            ]);

        $result = $models->paginate((int) $this->request->input('size', 10));
        $result = $result->toArray();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function post()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            // 检查 船期 是否存在
            if ($params['sail_schedule_id']) {
                $sailScheduleModel = WuliuSailSchedule::where('id', $params['sail_schedule_id'])
                    ->first();
                if (! $sailScheduleModel) {
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '船期不存在');
                }
                if (! $sailScheduleModel->ship_company_id) {
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '船期还未绑定船公司，请先绑定');
                }
            }

            if ($params['case_number']) {
                $existsModel = WuliuSeaWaybill::where('number', $params['number'])
                    ->where('case_number', $params['case_number'])
                    ->first();
                if ($existsModel) {
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '存在相同的海运单和箱号');
                }
            }

            $model = new WuliuSeaWaybill();
            if ($params['sail_schedule_id']) {
                // $model->ship_company_id = $sailScheduleModel->ship_company_id;
                $model->sail_schedule_id = $sailScheduleModel->id;
            }
            $model->number = $params['number'];
            $model->case_number = $params['case_number'] ?? '';
            $model->qf_number = $params['qf_number'] ?? '';
            $model->box = $params['box'] ?? '';
            $model->good_name = $params['good_name'] ?? '';
            $model->weight = $params['weight'] ?? '';
            $model->ship_company_towing_fee = $params['ship_company_towing_fee'];
            $model->car_fee = $params['car_fee'];
            $model->car_other_fee = $params['car_other_fee'];
            $model->car_other_fee_desc = $params['car_other_fee_desc'] ?? '';
            $model->receipt_status = $params['receipt_status'] ?? WuliuSeaWaybill::RECEIPT_STATUS_DEFAULT;
            $model->poundbill_status = $params['poundbill_status'] ?? WuliuSeaWaybill::POUNDBILL_STATUS_DEFAULT;
            $model->box_reporting_status = $params['box_reporting_status'] ?? WuliuSeaWaybill::BOX_REPORTING_STATUS_DEFAULT;
            $model->liaison = $params['liaison'] ?? '';
            $model->liaison_mobile = $params['liaison_mobile'] ?? '';
            $model->liaison_address_detail = $params['liaison_address_detail'] ?? '';
            $model->liaison_remark = $params['liaison_remark'] ?? '';
            $model->partner_id = $params['partner_id'] ?? null;
            $model->partner_towing_fee = $params['partner_towing_fee'] ?? null;
            $model->partner_overdue_fee = $params['partner_overdue_fee'] ?? null;
            $model->partner_stockpiling_fee = $params['partner_stockpiling_fee'] ?? null;
            $model->partner_print_fee = $params['partner_print_fee'] ?? null;
            $model->partner_clean_fee = $params['partner_clean_fee'] ?? null;
            $model->partner_other_fee = $params['partner_other_fee'] ?? null;
            $model->partner_other_fee_desc = $params['partner_other_fee_desc'] ?? null;
            $model->partner_stay_pole = $params['partner_stay_pole'] ?? null;
            $model->fh_status = $params['fh_status'];
            $model->rush_status = $params['rush_status'];
            $model->tos = $params['tos'] ?? WuliuSeaWaybill::TOS_DEFAULT;
            $model->type = $params['type'] ?? WuliuSeaWaybill::TYPE_JINKOU;
            $model->status = $params['status'] ?? WuliuSeaWaybill::STATUS_DEFAULT;
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

        Db::beginTransaction();
        try {
            $model = WuliuSeaWaybill::with([
                // 'shipCompanyBill',
                'motorcadeBill',
                'partnerBill',
                'selfBill',
            ])
                ->find($params['id']);
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '海运单不存在');
            }
            // 检查 船期 是否存在
            if ($params['sail_schedule_id']) {
                $sailScheduleModel = WuliuSailSchedule::where('id', $params['sail_schedule_id'])
                    ->first();
                if (! $sailScheduleModel) {
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '船期不存在');
                }
                if (! $sailScheduleModel->ship_company_id) {
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '船期还未绑定船公司，请先绑定');
                }
            }

            if ($params['case_number']) {
                $existsModel = WuliuSeaWaybill::where('id', '<>', $params['id'])
                    ->where('number', $params['number'])
                    ->where('case_number', $params['case_number'])
                    ->first();
                if ($existsModel) {
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '存在相同的海运单和箱号');
                }
            }

            // 账单 已对 无法修改
            $error = false;
            // if ($model->shipCompanyBill && $model->shipCompanyBill->status !== WuliuBill::STATUS_DEFAULT) {
            //     $error = true;
            // }
            if ($model->motorcadeBill && $model->motorcadeBill->status !== WuliuBill::STATUS_DEFAULT) {
                $error = true;
            }
            if ($model->partnerBill && $model->partnerBill->status !== WuliuBill::STATUS_DEFAULT) {
                $error = true;
            }
            if ($model->selfBill && $model->selfBill->status !== WuliuBill::STATUS_DEFAULT) {
                $error = true;
            }
            if ($error) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '海运单存在于账单中且账单状态不是默认，无法更改');
            }

            if ($params['sail_schedule_id']) {
                // $model->ship_company_id = $sailScheduleModel->ship_company_id;
                $model->sail_schedule_id = $sailScheduleModel->id;
            }
            $model->number = $params['number'];
            $model->case_number = $params['case_number'] ?? '';
            $model->qf_number = $params['qf_number'] ?? '';
            $model->box = $params['box'] ?? '';
            $model->good_name = $params['good_name'] ?? '';
            $model->weight = $params['weight'] ?? '';
            $model->ship_company_towing_fee = $params['ship_company_towing_fee'];
            $model->car_fee = $params['car_fee'];
            $model->car_other_fee = $params['car_other_fee'];
            $model->car_other_fee_desc = $params['car_other_fee_desc'] ?? '';
            $model->receipt_status = $params['receipt_status'] ?? WuliuSeaWaybill::RECEIPT_STATUS_DEFAULT;
            $model->poundbill_status = $params['poundbill_status'] ?? WuliuSeaWaybill::POUNDBILL_STATUS_DEFAULT;
            $model->box_reporting_status = $params['box_reporting_status'] ?? WuliuSeaWaybill::BOX_REPORTING_STATUS_DEFAULT;
            $model->liaison = $params['liaison'] ?? '';
            $model->liaison_mobile = $params['liaison_mobile'] ?? '';
            $model->liaison_address_detail = $params['liaison_address_detail'] ?? '';
            $model->liaison_remark = $params['liaison_remark'] ?? '';
            $model->partner_id = $params['partner_id'] ?? null;
            $model->partner_towing_fee = $params['partner_towing_fee'] ?? null;
            $model->partner_overdue_fee = $params['partner_overdue_fee'] ?? null;
            $model->partner_stockpiling_fee = $params['partner_stockpiling_fee'] ?? null;
            $model->partner_print_fee = $params['partner_print_fee'] ?? null;
            $model->partner_clean_fee = $params['partner_clean_fee'] ?? null;
            $model->partner_other_fee = $params['partner_other_fee'] ?? null;
            $model->partner_other_fee_desc = $params['partner_other_fee_desc'] ?? null;
            $model->partner_stay_pole = $params['partner_stay_pole'] ?? null;
            $model->fh_status = $params['fh_status'];
            $model->rush_status = $params['rush_status'];
            $model->tos = $params['tos'] ?? WuliuSeaWaybill::TOS_DEFAULT;
            $model->type = $params['type'] ?? WuliuSeaWaybill::TYPE_JINKOU;
            $model->status = $params['status'] ?? WuliuSeaWaybill::STATUS_DEFAULT;
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
            $models = WuliuSeaWaybill::with([
                'shipCompanyBill',
                'motorcadeBill',
                'partnerBill',
                'selfBill',
            ])
                ->whereIn('id', $params)
                ->get();

            if (! $models->count()) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '海运单不存在');
            }
            foreach ($models as $key => $model) {
                // 账单 已对 无法删除
                $error = false;
                if ($model->shipCompanyBill && $model->shipCompanyBill->status !== WuliuBill::STATUS_DEFAULT) {
                    $error = true;
                }
                if ($model->motorcadeBill && $model->motorcadeBill->status !== WuliuBill::STATUS_DEFAULT) {
                    $error = true;
                }
                if ($model->partnerBill && $model->partnerBill->status !== WuliuBill::STATUS_DEFAULT) {
                    $error = true;
                }
                if ($model->selfBill && $model->selfBill->status !== WuliuBill::STATUS_DEFAULT) {
                    $error = true;
                }
                if ($error) {
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '海运单{id:' . $model->id . '}存在于账单中，无法删除');
                }

                $model->delete();
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function updateZongbiao()
    {
        // $params = $this->getRequestAllFilter();

        try {
            $file = $this->request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            // $spreadsheet = WuliuSpreadsheetService::getUtf8Context($spreadsheet, $fileEncoding);
            $filename = $file->getClientFilename();
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员' . $e->getMessage() . $file->getRealPath());
        }

        Db::beginTransaction();
        try {
            // 读取文件
            $dataArray = $spreadsheet->getActiveSheet()
                ->toArray();
            Tools::paramsFilter($dataArray);
            $result = $this->seaWaybillService->updateZongbiao($dataArray);
            $insertCount = $result['insertCount'];
            $insertData = $result['insertData'];
            $updateData = $result['updateCount'];
            Db::commit();
            return $this->resJson("共导入{$insertCount}条，更新{$updateCount}条数据！", [
                $insertData,
                $updateData,
            ]);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function copy()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            $baseModel = WuliuSeaWaybill::with(['sailSchedule'])
                ->find($params['id']);
            if (! $baseModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '海运单不存在');
            }

            if ($params['case_numbers']) {
                $case_numbers = explode(PHP_EOL, $params['case_numbers']);
                // var_dump($case_numbers);
                $count = count($case_numbers);
            } else {
                if (! isset($params['count'])) {
                    $count = 1;
                } else {
                    $count = (int) $params['count'];
                }
            }
            if ($count > 100) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数量不能大于100');
            }

            for ($i = 0; $i < $count; ++$i) {
                $model = new WuliuSeaWaybill();
                if ($baseModel->sailSchedule) {
                    // $model->ship_company_id = $baseModel->sailSchedule->ship_company_id;
                    $model->sail_schedule_id = $baseModel->sailSchedule->id;
                }
                $model->number = $baseModel->number;
                if (isset($case_numbers)) {
                    $model->case_number = trim($case_numbers[$i]);

                    if ($this->seaWaybillService->checkExistsSingle($model->number, $model->case_number)) {
                        throw new ServiceException(ServiceCode::ERROR, [], 400, [], '海运单：' . $model->number . ' 箱号：' . $model->case_number . '已存在');
                    }
                }

                $model->qf_number = '';
                $model->box = $baseModel->box;
                $model->good_name = $baseModel->good_name;
                $model->weight = $baseModel->weight;
                $model->ship_company_towing_fee = $baseModel->ship_company_towing_fee;
                # 合作公司 二段派车费
                $model->partner_towing_fee = $baseModel->partner_towing_fee;
                $model->partner_overdue_fee = $baseModel->partner_overdue_fee;
                $model->partner_stockpiling_fee = $baseModel->partner_stockpiling_fee;
                $model->partner_thc_fee = $baseModel->partner_thc_fee;
                $model->partner_print_fee = $baseModel->partner_print_fee;
                $model->partner_clean_fee = $baseModel->partner_clean_fee;
                $model->partner_other_fee = $baseModel->partner_other_fee;
                $model->partner_other_fee_desc = $baseModel->partner_other_fee_desc;
                $model->partner_stay_pole = $baseModel->partner_stay_pole;
                $model->partner_remarks = $baseModel->partner_remarks;

                $model->car_fee = $baseModel->car_fee;
                $model->car_other_fee = $baseModel->car_other_fee;
                $model->car_other_fee_desc = $baseModel->car_other_fee_desc;
                $model->receipt_status = WuliuSeaWaybill::RECEIPT_STATUS_DEFAULT;
                $model->poundbill_status = WuliuSeaWaybill::POUNDBILL_STATUS_DEFAULT;
                $model->box_reporting_status = WuliuSeaWaybill::BOX_REPORTING_STATUS_DEFAULT;
                $model->liaison = $baseModel->liaison;
                $model->liaison_mobile = $baseModel->liaison_mobile;
                $model->liaison_address_detail = $baseModel->liaison_address_detail;
                $model->liaison_remark = $baseModel->liaison_remark;
                $model->tos = $baseModel->tos;
                $model->type = $baseModel->type;
                $model->fh_status = $baseModel->fh_status;
                // $model->partner_id = $params['partner_id'] ?? null;
                // $model->partner_towing_fee = $params['partner_towing_fee'] ?? null;
                // $model->partner_overdue_fee = $params['partner_overdue_fee'] ?? null;
                // $model->partner_stockpiling_fee = $params['partner_stockpiling_fee'] ?? null;
                // $model->partner_print_fee = $params['partner_print_fee'] ?? null;
                // $model->partner_clean_fee = $params['partner_clean_fee'] ?? null;
                // $model->partner_other_fee = $params['partner_other_fee'] ?? null;
                // $model->partner_other_fee_desc = $params['partner_other_fee_desc'] ?? null;
                // $model->partner_stay_pole = $params['partner_stay_pole'] ?? null;
                // $model->rush_status = $params['rush_status'];
                // $model->status = $params['status'] ?? WuliuSeaWaybill::STATUS_DEFAULT;
                $model->save();
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function paiche()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            // 查看车辆是否存在
            $carModel = WuliuCar::find($params['car_id']);
            if (! $carModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '车辆不存在');
            }

            // 检查海运单
            $params['ids'] = array_unique($params['ids']);
            $models = WuliuSeaWaybill::whereIn('id', $params['ids'])
                ->get();
            // var_dump($models->count());
            if (! $models->count()) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '需要操作的数据为空');
            }
            if ($models->count() != count($params['ids'])) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '部分数据不存在，请刷新页面重试');
            }
            // 查看是否有 已经派车的数据，报错
            $existsErrorArray = [];
            foreach ($models as $model) {
                if ($model->car_id) {
                    $existsErrorArray[] = $model->case_number;
                }
            }
            if ($existsErrorArray) {
                $text = '箱号：';
                $text .= implode(',', $existsErrorArray);
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], $text . '已派车，请先取消派车');
            }

            // 修改
            foreach ($models as $model) {
                $model->car_id = $carModel->id;
                $model->car_finished_date = $params['car_finished_date'];
                $model->save();
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function paicheCancel()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            // 检查海运单
            $params['ids'] = array_unique($params['ids']);
            $models = WuliuSeaWaybill::whereIn('id', $params['ids'])
                ->get();
            // var_dump($models->count());
            if (! $models->count()) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '需要删除的数据为空');
            }
            if ($models->count() != count($params['ids'])) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '部分数据不存在，请刷新页面重试');
            }

            // 查看是否有 已经派车的数据，报错
            // $existsErrorArray = [];
            // foreach ($models as $model) {
            //     if ($model->car_id) {
            //         $existsErrorArray[] = $model->case_number;
            //     }
            // }
            // if ($existsErrorArray) {
            //     $text = '箱号：';
            //     $text .= implode(',', $existsErrorArray);
            //     throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, $text . '已派车，请先取消派车');
            // }

            // 修改
            foreach ($models as $model) {
                $model->car_id = null;
                $model->car_finished_date = null;
                $model->save();
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function billLuru()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        Db::beginTransaction();
        try {
            // 查看车辆是否存在
            $billModel = WuliuBill::find($params['bill_id']);
            if (! $billModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '账单不存在');
            }
            if ($billModel->status !== WuliuBill::STATUS_DEFAULT) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '账单状态不是默认，无法修改');
            }

            // 检查海运单
            $params['ids'] = array_unique($params['ids']);
            $models = WuliuSeaWaybill::whereIn('id', $params['ids'])
                ->get();
            // var_dump($models->count());
            if (! $models->count()) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '需要操作的数据为空');
            }
            if ($models->count() != count($params['ids'])) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '部分数据不存在，请刷新页面重试');
            }

            // 修改
            $type = $billModel->type;
            // var_dump($type);
            switch ($type) {
                case WuliuBill::TYPE_SHIP_COMPANY:
                    $field = 'ship_company_bill_id';
                    break;
                case WuliuBill::TYPE_MOTORCADE:
                    $field = 'motorcade_bill_id';
                    break;
                case WuliuBill::TYPE_PARTNER:
                    $field = 'partner_bill_id';
                    break;
                case WuliuBill::TYPE_SELF:
                    $field = 'self_bill_id';
                    break;
                default:
                    throw new LogicException('不支持的类型');
            }
            foreach ($models as $model) {
                if ($type === WuliuBill::TYPE_SELF) {
                    // 如果绑定的是车辆，则要检查车辆是否派车
                    if (! $model->car_id) {
                        throw new ServiceException(ServiceCode::ERROR, [], 400, [], "海运单{{$model->number}}未派车，请先处理");
                    }
                }

                # 如果绑定的是车队或个人 要先检测是否已绑定 自己司机，反之也检测
                if ($type === WuliuBill::TYPE_SELF) {
                    // 如果绑定的是车辆，则要检查车辆是否派车
                    if ($model->motorcade_bill_id) {
                        throw new ServiceException(ServiceCode::ERROR, [], 400, [], "海运单{{$model->number}}已绑定车队或个人");
                    }
                } elseif ($type === WuliuBill::TYPE_MOTORCADE) {
                    // 如果绑定的是车辆，则要检查车辆是否派车
                    if ($model->self_bill_id) {
                        throw new ServiceException(ServiceCode::ERROR, [], 400, [], "海运单{{$model->number}}已绑定车队或个人");
                    }
                }

                if ($model->{$field}) {
                    // 如果海运单已经绑定订单，则不允许操作
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], "海运单{{$model->number}}已绑定账单，请先处理");
                }
                $model->{$field} = $billModel->id;
                $model->save();
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function billLuruCancel()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        Db::beginTransaction();
        try {
            // 查看车辆是否存在
            $billModel = WuliuBill::find($params['bill_id']);
            if (! $billModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '账单不存在');
            }
            if ($billModel->status !== WuliuBill::STATUS_DEFAULT) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '账单状态不是默认，无法修改');
            }

            // 检查海运单
            $params['ids'] = array_unique($params['ids']);
            $models = WuliuSeaWaybill::whereIn('id', $params['ids'])
                // ->with([
                //     'shipCompanyBill',
                //     'motorcadeBill',
                //     'partnerBill',
                //     'selfBill',
                // ])
                ->get();
            // var_dump($models->count());
            if (! $models->count()) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '需要操作的数据为空');
            }
            if ($models->count() != count($params['ids'])) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '部分数据不存在，请刷新页面重试');
            }

            // 修改
            $type = $billModel->type;
            // var_dump($type);
            switch ($type) {
                case WuliuBill::TYPE_SHIP_COMPANY:
                    $field = 'ship_company_bill_id';
                    break;
                case WuliuBill::TYPE_MOTORCADE:
                    $field = 'motorcade_bill_id';
                    break;
                case WuliuBill::TYPE_PARTNER:
                    $field = 'partner_bill_id';
                    break;
                case WuliuBill::TYPE_SELF:
                    $field = 'self_bill_id';
                    break;
                default:
                    throw new LogicException('不支持的类型');
            }
            foreach ($models as $model) {
                if (! $model->{$field}) {
                    // 如果海运单已经绑定订单，则不允许操作
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], "海运单{{$model->number}}未绑定账单，不用处理");
                }
                $model->{$field} = null;
                $model->save();
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function bindPartner()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            $partnerModel = WuliuPartner::find($params['partner_id']);
            if (! $partnerModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '合作公司或个人不存在');
            }

            // 检查海运单
            $params['ids'] = array_unique($params['ids']);
            $models = WuliuSeaWaybill::whereIn('id', $params['ids'])
                ->get();
            // var_dump($models->count());
            if (! $models->count()) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '需要操作的数据为空');
            }
            if ($models->count() != count($params['ids'])) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '部分数据不存在，请刷新页面重试');
            }

            $updateData = [];
            foreach ($params['ids'] as $value) {
                $updateData[] = [
                    'id' => $value,
                    'partner_id' => $params['partner_id'],
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }

            if ($updateData) {
                (new WuliuSeaWaybill())->updateBatch($updateData);
                Db::commit();
            }

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function bindPartnerCancel()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        Db::beginTransaction();
        try {
            // 检查海运单
            $params['ids'] = array_unique($params['ids']);
            $models = WuliuSeaWaybill::whereIn('id', $params['ids'])
                ->get();
            // var_dump($models->count());
            if (! $models->count()) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '需要操作的数据为空');
            }
            if ($models->count() != count($params['ids'])) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '部分数据不存在，请刷新页面重试');
            }

            $updateData = [];
            foreach ($params['ids'] as $value) {
                $updateData[] = [
                    'id' => $value,
                    'partner_id' => null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ];
            }

            if ($updateData) {
                (new WuliuSeaWaybill())->updateBatch($updateData);
                Db::commit();
            }

            return $this->responseJson(ServiceCode::SUCCESS);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function downloadReceipt()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        // Db::beginTransaction();
        try {
            // 查看车辆是否存在
            if (! isset($params['ids']) || ! $params['ids'] || ! is_array($params['ids'])) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '参数错误');
            }
            $models = WuliuSeaWaybill::with(['sailSchedule'])
                ->find($params['ids']);
            if (! $models) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据不存在');
            }
            if ($models->count() != count($params['ids'])) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据数量有误');
            }
            $result = $this->seaWaybillService->getReceipt($models);
            // 将文件内容作为响应体返回
            return $this->response->download($result['path'], $result['filename']);
        } catch (Exception $e) {
            // Db::rollBack();
            throw $e;
        }
    }

    public function zjportSelect()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        // Db::beginTransaction();
        // PageCnt 格式，1不带序号 3带序号
        // http://219.132.70.181:8000/GetPageData.ashx?ReqDataID=WebLastConrSt&Con1=ZGXU6150331&PageCnt=3&PageNo=1
        $case_number = $params['case_number'];
        $url = 'https://icttc.zjport.com/query/GetPageData.ashx?ReqDataID=WebLastConrSt&Con1=' . $case_number . '&PageCnt=1&PageNo=1';

        $ch = curl_init();
        $timeout = 5;
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        // curl_setopt($ch, CURLOPT_PROXYUSERPWD, ":"); //http代理认证帐号，username:password的格式
        curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // 使用http代理模式
        $file_contents = curl_exec($ch);
        curl_close($ch);

        // Db::commit();

        return $this->responseJson(ServiceCode::SUCCESS, $file_contents);
    }

    public function importShoudongdan()
    {
        $params = $this->getRequestAllFilter();
        try {
            $file = $this->request->file('file');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        Db::beginTransaction();
        try {
            $numberName = '运单号';
            $case_numberName = '箱号';
            $qf_numberName = '铅封号';
            $liaison_Name = '联系人';
            $liaison_mobileName = '联系人电话';
            $liaison_address_detailName = '联系人详细地址';
            $good_nameName = '货名';
            $boxName = '箱型';
            $car_numberName = '车辆 派送日期';
            $typeName = '进出口';
            $tosName = '业务类型';
            $partner_Name = '合作公司或个人(对账全称)';
            $partner_towing_feeName = '二段拖车费';
            $partner_overdue_feeName = '船公司：滞箱费/超期费';
            $partner_stockpiling_feeName = '码头：堆存费';
            $partner_thc_feeName = '码头：装卸作业费(THC)';
            $partner_print_feeName = '打单费';
            $partner_clean_feeName = '洗柜费';
            $partner_other_feeName = '其他费用';
            $partner_other_fee_descName = '其他费用说明';
            $partner_stay_poleName = '加固杆';
            $partner_remarksName = '备注';
            $created_atName = '创建日期';
            foreach ($dataArray[0] as $index => $value) {
                if ($numberName === $value) {
                    $numberIndex = $index;
                    continue;
                }
                if ($case_numberName === $value) {
                    $case_numberIndex = $index;
                    continue;
                }
                if ($qf_numberName === $value) {
                    $qf_numberIndex = $index;
                    continue;
                }
                if ($liaison_address_detailName === $value) {
                    $liaison_address_detailIndex = $index;
                    continue;
                }
                if ($liaison_Name === $value) {
                    $liaison_Index = $index;
                    continue;
                }
                if ($liaison_mobileName === $value) {
                    $liaison_mobileIndex = $index;
                    continue;
                }
                if ($car_numberName === $value) {
                    $car_numberIndex = $index;
                    continue;
                }
                if ($good_nameName === $value) {
                    $good_nameIndex = $index;
                    continue;
                }
                if ($boxName === $value) {
                    $boxIndex = $index;
                    continue;
                }
                if ($typeName === $value) {
                    $typeIndex = $index;
                    continue;
                }
                if ($tosName === $value) {
                    $tosIndex = $index;
                    continue;
                }
                if ($partner_Name === $value) {
                    $partner_Index = $index;
                    continue;
                }
                if ($partner_towing_feeName === $value) {
                    $partner_towing_feeIndex = $index;
                    continue;
                }
                if ($partner_thc_feeName === $value) {
                    $partner_thc_feeIndex = $index;
                    continue;
                }
                if ($partner_overdue_feeName === $value) {
                    $partner_overdue_feeIndex = $index;
                    continue;
                }
                if ($partner_stockpiling_feeName === $value) {
                    $partner_stockpiling_feeIndex = $index;
                    continue;
                }
                if ($partner_print_feeName === $value) {
                    $partner_print_feeIndex = $index;
                    continue;
                }
                if ($partner_clean_feeName === $value) {
                    $partner_clean_feeIndex = $index;
                    continue;
                }
                if ($partner_other_feeName === $value) {
                    $partner_other_feeIndex = $index;
                    continue;
                }
                if ($partner_other_fee_descName === $value) {
                    $partner_other_fee_descIndex = $index;
                    continue;
                }
                if ($partner_stay_poleName === $value) {
                    $partner_stay_poleIndex = $index;
                    continue;
                }
                if ($partner_remarksName === $value) {
                    $partner_remarksIndex = $index;
                    continue;
                }
                if ($created_atName === $value) {
                    $created_atIndex = $index;
                    continue;
                }
            }

            if (! isset($tosIndex, $typeIndex, $numberIndex, $case_numberIndex, $qf_numberIndex, $liaison_address_detailIndex, $boxIndex, $liaison_Index, $liaison_mobileIndex, $car_numberIndex, $partner_Index, $partner_towing_feeIndex, $partner_thc_feeIndex, $partner_overdue_feeIndex, $partner_stockpiling_feeIndex, $partner_print_feeIndex, $partner_clean_feeIndex, $partner_other_feeIndex, $partner_other_fee_descIndex, $partner_stay_poleIndex, $partner_remarksIndex, $good_nameIndex, $created_atIndex)) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '文件有误，请联系开发人员');
            }
            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $carMatchingModels = WuliuCar::get();
            $carMatchingModelsArray = $carMatchingModels->toArray();
            // $partnerName = $dataArray[0][1];
            // var_dump($partnerName);
            $partnerMatchingModels = WuliuPartner::get();
            $partnerMatchingModelsArray = $partnerMatchingModels->toArray();
            $insertData = $newCarData = $updateData = [];
            foreach ($dataArray as $key => &$value) {
                if ($key < 1) {
                    continue;
                }

                # 车辆 + 派送日期
                $a0 = $value[$car_numberIndex];
                if ($value[$car_numberIndex]) {
                    $value[$car_numberIndex] = str_replace("\t", ' ', $value[$car_numberIndex]);
                    $value[$car_numberIndex] = str_replace('     ', ' ', $value[$car_numberIndex]);
                    $value[$car_numberIndex] = str_replace('    ', ' ', $value[$car_numberIndex]);
                    $value[$car_numberIndex] = str_replace('   ', ' ', $value[$car_numberIndex]);
                    $value[$car_numberIndex] = str_replace('  ', ' ', $value[$car_numberIndex]);
                    $value[$car_numberIndex] = str_replace([
                        '送',
                        '有地',
                        ' 忆',
                        '忆',
                        ' 已',
                        '已',
                        '对',
                        '四',
                        '哥',
                        '已對',
                        '付',
                        '收',
                        '装',
                    ], '', $value[$car_numberIndex]);
                    $array = explode(' ', $value[$car_numberIndex]);
                    if (count($array) != 2) {
                        var_dump($array, $a0, $car_numberIndex);
                        return $this->responseJson(ServiceCode::SUCCESS, $value[$car_numberIndex]);
                    }
                    $car_number = $array[0];
                    $car_id = WuliuCar::getIdByNumber($carMatchingModelsArray, $car_number);
                    if (! $car_id) {
                        // 自动创建
                        $carModel = new WuliuCar();
                        $carModel->number = $car_number;
                        $carModel->save();
                        $carMatchingModelsArray[] = $carModel->toArray();
                        $value['car_id'] = $carModel->id;
                        $newCarData[] = $carModel->number;

                        // 不自动创建
                        // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "车牌号{{$numberTemp}}不存在，请先创建");
                    }
                    $value['car_id'] = $car_id;
                    if (isset($array[1])) {
                        $car_finished_date = trim($array[1]);
                        $timeStr = str_replace([
                            '送',
                            "\t",
                            '发货方日鑫鱼粉厂',
                            '有地',
                            ' 忆',
                            '忆',
                            '已',
                            '对',
                            '對',
                            '阿海',
                        ], '', $car_finished_date);
                        $a1 = $timeStr;

                        $timeStr = str_replace('号', '日', $timeStr);
                        $a2 = $timeStr;
                        $timeStr = str_replace([
                            '号',
                            '日',
                            '提',
                        ], '', $timeStr);
                        $a3 = $timeStr;
                        // var_dump($timeStr);
                        $timeStr = str_replace([
                            '年',
                            '月',
                            '日',
                            '号',
                        ], '-', $timeStr);
                        $a4 = $timeStr;
                        $timeStr = str_replace('.', '-', $timeStr);
                        $a5 = $timeStr;

                        if (! $timestamp = strtotime($timeStr)) {
                            throw new ServiceException(ServiceCode::ERROR, [], 400, [], $a1 . $a2 . $a3 . $a4 . $a5 . '---' . $car_finished_date);
                        }
                        // var_dump($timeStr);
                        // var_dump($timestamp);
                        $value['car_finished_date'] = date('Y-m-d', $timestamp);
                    } else {
                        var_dump($array);
                    }
                }

                if ($value[$partner_Index]) {
                    $partner_id = WuliuPartner::getIdByName($partnerMatchingModelsArray, $value[$partner_Index]);
                    if (! $partner_id) {
                        throw new ServiceException(ServiceCode::ERROR, [], 400, [], $value[$partner_Index] . '不存在');
                    }
                } else {
                    $partner_id = null;
                }

                $seaWaybillSaveArray = [];
                $seaWaybillSaveArray['partner_id'] = $partner_id;
                if ($value[$created_atIndex]) {
                    $seaWaybillSaveArray['created_at'] = $value[$created_atIndex];
                }
                /*
                 * 接下来是update partner data
                 */
                $seaWaybillSaveArray['liaison'] = $value[$liaison_Index] ?: '';
                $seaWaybillSaveArray['liaison_mobile'] = $value[$liaison_mobileIndex] ?: '';
                $seaWaybillSaveArray['liaison_address_detail'] = $value[$liaison_address_detailIndex] ?: '';
                $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'] ?? null;
                $seaWaybillSaveArray['car_id'] = $value['car_id'] ?? null;
                # ----------------- 替换 -----------------
                $abcdefg = <<<'a'
                $seaWaybillSaveArray['liaison_address_detail'] = $value[$liaison_address_detailIndex] ?: '';
                $seaWaybillSaveArray['partner_bill_id'] = 25;
a;

                $seaWaybillSaveArray['partner_towing_fee'] = $value[$partner_towing_feeIndex] ?: 0;
                $seaWaybillSaveArray['partner_thc_fee'] = $value[$partner_thc_feeIndex] ?: 0;
                $seaWaybillSaveArray['partner_overdue_fee'] = $value[$partner_overdue_feeIndex] ?: 0;
                $seaWaybillSaveArray['partner_stockpiling_fee'] = $value[$partner_stockpiling_feeIndex] ?: 0;
                $seaWaybillSaveArray['partner_print_fee'] = $value[$partner_print_feeIndex] ?: 0;
                $seaWaybillSaveArray['partner_clean_fee'] = $value[$partner_clean_feeIndex] ?: 0;
                $seaWaybillSaveArray['partner_other_fee'] = $value[$partner_other_feeIndex] ?: 0;
                $seaWaybillSaveArray['partner_other_fee_desc'] = $value[$partner_other_fee_descIndex] ?: '';
                $seaWaybillSaveArray['partner_stay_pole'] = $value[$partner_stay_poleIndex] ?: 0;
                $seaWaybillSaveArray['partner_remarks'] = $value[$partner_remarksIndex] ?: '';

                $existsModel = WuliuSeaWaybill::getByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if ($existsModel) {
                    $seaWaybillSaveArray['id'] = $existsModel['id'];
                    $seaWaybillSaveArray['updated_at'] = $value['car_finished_date'] ?? '2023-01-13';
                    $updateData[] = $seaWaybillSaveArray;
                // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, $value[$numberIndex] . $value[$case_numberIndex] . '已存在');
                } else {
                    switch ($value[$typeIndex]) {
                        case '进口':
                            $seaWaybillSaveArray['type'] = WuliuSeaWaybill::TYPE_JINKOU;
                            break;
                        case '出口':
                            $seaWaybillSaveArray['type'] = WuliuSeaWaybill::TYPE_CHUKOU;
                            break;
                        default:
                            $seaWaybillSaveArray['type'] = WuliuSeaWaybill::TYPE_DEFAULT;
                            break;
                    }
                    $seaWaybillSaveArray['tos'] = $value[$tosIndex] ?? WuliuSeaWaybill::TOS_DEFAULT;
                    $ship_company_id = WuliuShipCompany::getIdBySeaWaybillNumber($value[$numberIndex]);
                    if (! $seaWaybillSaveArray['type']) {
                        switch ($ship_company_id) {
                            case WuliuShipCompany::ANTONG:
                                // 默认出口
                                $seaWaybillSaveArray['type'] = WuliuSeaWaybill::TYPE_CHUKOU;
                                break;
                            case WuliuShipCompany::ZHONGGU:
                                // 进出口都有
                            default:
                                $seaWaybillSaveArray['type'] = WuliuSeaWaybill::TYPE_DEFAULT;
                                break;
                        }
                    }
                    $seaWaybillSaveArray['number'] = $value[$numberIndex];
                    $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                    $seaWaybillSaveArray['qf_number'] = $value[$qf_numberIndex];
                    $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex] ?: '';
                    $seaWaybillSaveArray['box'] = $value[$boxIndex] ?: '';
                    if (! $seaWaybillSaveArray['created_at']) {
                        $seaWaybillSaveArray['created_at'] = $value['car_finished_date'] ?? '2023-01-13';
                    }
                    $insertData[] = $seaWaybillSaveArray;
                }
            }
            $insertCount = 0;
            $updateCount = 0;
            // var_dump(count($insertData));
            // return $this->responseJson(ServiceCode::SUCCESS, $insertData);
            if ($insertData) {
                $insertCount = count($insertData);
                (new WuliuSeaWaybill())->insert($insertData);
            }
            if ($updateData) {
                // $updateIds = [];
                // foreach ($updateData as $value) {
                //     $updateIds[] = $value['id'];
                // }
                // $upadteIdsStr = implode(',', $updateIds);
                $updateCount = count($updateData);
                // (new WuliuSeaWaybill())->updateBatch($updateData);
            }

            Db::commit();

            return $this->resJson("共导入{$insertCount}条，更新{$updateCount}条数据！", [
                $insertData,
                $updateData,
            ]);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function importMen()
    {
        // $params = $this->getRequestAllFilter();
        try {
            $file = $this->request->file('file');
            $spreadsheet = IOFactory::load($file->getRealPath());
            // $spreadsheet = WuliuSpreadsheetService::getUtf8Context($spreadsheet, $fileEncoding);
            $filename = $file->getClientFilename();
        } catch (ReaderExcetpion $e) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '文件有误，请联系开发人员' . $e->getMessage() . $file->getRealPath());
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        Db::beginTransaction();
        try {
            $sail_schedule_nameFieldArray = ['船名'];
            $sail_schedule_voyageFieldArray = ['航次'];
            $sail_schedule_arrival_dateFieldArray = ['卸船日期'];
            $sail_scheduleFieldArray = ['船名航次'];
            $qiyungangFieldArray = ['起运港'];
            $mudigangFieldArray = ['目的港'];
            $numberFieldArray = ['运单号'];
            $case_numberFieldArray = ['箱号'];
            $fh_statusFieldArray = ['放货状态'];
            $receipt_statusFieldArray = ['客户签收单'];
            $ship_company_towing_feeFieldArray = [
                '拖车费',
                '请派价',
            ];
            $typeFieldArray = ['进口出口'];
            $weightFieldArray = ['单箱重量'];
            $good_nameFieldArray = ['货名'];
            $boxFieldArray = [
                '箱型',
                '箱型尺寸',
            ];
            $qf_numberFieldArray = [
                '铅封号',
                '封号',
                '铅封号(客户)',
            ];
            $liaison_FieldArray = [
                '联系人',
                '收货人',
            ];
            $liaison_mobileFieldArray = [
                '联系人电话',
                '联系电话',
            ];
            $liaison_address_detailFieldArray = [
                '联系人详细地址',
                '详细地址',
                '收货地址',
                '联系人/联系电话/收货地址',
                '联系人/联系电话/发货地址',
            ];
            $remarkFieldArray = ['地址备注'];
            $liaison_remarkFieldArray = ['派车要求'];
            $created_atArray = ['派车时间']; # '要求装货时间', '要求送货时间',
            // $car_numberFieldArray = '车辆 派送日期';
            // $partner_towing_feeFieldArray = '二段拖车费';
            // $partner_overdue_feeFieldArray = '船公司：滞箱费/超期费';
            // $partner_stockpiling_feeFieldArray = '码头：堆存费';
            // $partner_print_feeFieldArray = '打单费';
            // $partner_clean_feeFieldArray = '洗柜费';
            // $partner_other_feeFieldArray = '其他费用';
            // $partner_other_fee_descFieldArray = '其他费用说明';
            // $partner_stay_poleFieldArray = '加固杆';
            // $partner_remarksFieldArray = '备注';
            $only_notID = 99999999999;
            $mudigangIndex = $qiyungangIndex = $sail_schedule_nameIndex = $sail_schedule_nameIndex = $sail_schedule_voyageIndex = $sail_schedule_arrival_dateIndex = $sail_scheduleIndex = $numberIndex = $case_numberIndex = $qf_numberIndex = $good_nameIndex = $boxIndex = $weightIndex = $fh_statusIndex = $receipt_statusIndex = $ship_company_towing_feeIndex = $liaison_Index = $liaison_mobileIndex = $liaison_address_detailIndex = $liaison_remarkIndex = $typeIndex = $created_atIndex = $remarkIndex = $only_notID;
            foreach ($dataArray[0] as $index => $value) {
                if (in_array($value, $mudigangFieldArray)) {
                    $mudigangIndex = $index;
                    continue;
                }
                if (in_array($value, $qiyungangFieldArray)) {
                    $qiyungangIndex = $index;
                    continue;
                }
                if (in_array($value, $sail_schedule_nameFieldArray)) {
                    $sail_schedule_nameIndex = $index;
                    continue;
                }
                if (in_array($value, $sail_schedule_voyageFieldArray)) {
                    $sail_schedule_voyageIndex = $index;
                    continue;
                }
                if (in_array($value, $sail_schedule_arrival_dateFieldArray)) {
                    $sail_schedule_arrival_dateIndex = $index;
                    continue;
                }
                if (in_array($value, $sail_scheduleFieldArray)) {
                    $sail_scheduleIndex = $index;
                    continue;
                }
                if (in_array($value, $numberFieldArray)) {
                    $numberIndex = $index;
                    continue;
                }
                if (in_array($value, $case_numberFieldArray)) {
                    $case_numberIndex = $index;
                    continue;
                }
                if (in_array($value, $qf_numberFieldArray)) {
                    $qf_numberIndex = $index;
                    continue;
                }
                if (in_array($value, $good_nameFieldArray)) {
                    $good_nameIndex = $index;
                    continue;
                }
                if (in_array($value, $boxFieldArray)) {
                    $boxIndex = $index;
                    continue;
                }
                if (in_array($value, $weightFieldArray)) {
                    $weightIndex = $index;
                    continue;
                }
                if (in_array($value, $fh_statusFieldArray)) {
                    $fh_statusIndex = $index;
                    continue;
                }
                if (in_array($value, $receipt_statusFieldArray)) {
                    $receipt_statusIndex = $index;
                    continue;
                }
                if (in_array($value, $ship_company_towing_feeFieldArray)) {
                    $ship_company_towing_feeIndex = $index;
                    continue;
                }
                if (in_array($value, $liaison_FieldArray)) {
                    $liaison_Index = $index;
                    continue;
                }
                if (in_array($value, $liaison_mobileFieldArray)) {
                    $liaison_mobileIndex = $index;
                    continue;
                }
                if (in_array($value, $liaison_address_detailFieldArray)) {
                    $liaison_address_detailIndex = $index;
                    continue;
                }
                if (in_array($value, $liaison_remarkFieldArray)) {
                    $liaison_remarkIndex = $index;
                    continue;
                }
                if (in_array($value, $remarkFieldArray)) {
                    $remarkIndex = $index;
                    continue;
                }
                if (in_array($value, $typeFieldArray)) {
                    $typeIndex = $index;
                    continue;
                }
                if (in_array($value, $created_atArray)) {
                    $created_atIndex = $index;
                    continue;
                }
            }

            // if (! isset($mudigangIndex, $qiyungangIndex, $sail_schedule_nameIndex,$sail_schedule_voyageIndex,$sail_schedule_arrival_dateIndex,$sail_scheduleIndex,$numberIndex, $case_numberIndex,$qf_numberIndex, $weightIndex,$fh_statusIndex,$receipt_statusIndex, $ship_company_towing_feeInde,$liaison_address_detailIndex,$boxIndex,$liaison_Index,$liaison_mobileIndex, $good_nameIndex,$liaison_remarkIndex,$typeIndex)) {
            //     throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件第一行标题格式有误');
            // }
            $common_ship_company_id = WuliuShipCompany::getIdByFullName($filename);
            if (strpos($filename, '进口') !== false) {
                $type = WuliuSeaWaybill::TYPE_JINKOU;
            } elseif (strpos($filename, '出口') !== false) {
                $type = WuliuSeaWaybill::TYPE_CHUKOU;
            } else {
                $type = false;
            }
            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $carMatchingModels = WuliuCar::get();
            $carMatchingModelsArray = $carMatchingModels->toArray();
            $sailScheduleMatchingModels = WuliuSailSchedule::get();
            $sailScheduleMatchingModelsArray = $sailScheduleMatchingModels->toArray();
            $insertData = $updateData = [];
            // return $this->responseJson(ServiceCode::SUCCESS, $dataArray);

            foreach ($dataArray as $key => &$value) {
                if ($key < 1) {
                    continue;
                }
                $seaWaybillSaveArray = [];
                $value[$only_notID] = '';

                // 船期处理
                if ($value[$sail_schedule_nameIndex] && $value[$sail_schedule_voyageIndex]) {
                    $sail_schedule_name = $value[$sail_schedule_nameIndex];
                    $sail_schedule_voyage = $value[$sail_schedule_voyageIndex];
                // var_dump($sail_schedule_id);
                // return $this->responseJson(ServiceCode::SUCCESS, 1);
                // return $this->responseJson(ServiceCode::SUCCESS, [$sailScheduleMatchingModelsArray, $sail_schedule_id, $ship_company_id, $sail_schedule_array[0], $sail_schedule_array[1]]);
                // var_dump();
                } elseif ($value[$sail_scheduleIndex]) {
                    $sail_schedule_array = $this->getImportFotmatSailScheduleNameAndVoyavge($value[$sail_scheduleIndex]);
                    if (! $sail_schedule_array) {
                        throw new ServiceException(ServiceCode::ERROR, [], 400, [], '船期航次数据有误:' . $value[$sail_scheduleIndex]);
                    }
                    $sail_schedule_name = $sail_schedule_array[0];
                    $sail_schedule_voyage = $sail_schedule_array[1];
                } else {
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '无法匹配出船期数据');
                }
                if ($common_ship_company_id) {
                    $ship_company_id = $common_ship_company_id;
                } else {
                    $ship_company_id = WuliuShipCompany::getIdBySeaWaybillNumber($value[$numberIndex]);
                }
                if (! $ship_company_id) {
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '运单号：' . $value[$numberIndex] . '匹配不到船公司');
                }
                $sail_schedule_id = WuliuSailSchedule::getIdByShipCompanyNameAndNameAndVoyage($sailScheduleMatchingModelsArray, $ship_company_id, $sail_schedule_name, $sail_schedule_voyage);
                if (! $sail_schedule_id) {
                    $sailScheduleModel = new WuliuSailSchedule();
                    $sailScheduleModel->name = $sail_schedule_name;
                    $sailScheduleModel->voyage = $sail_schedule_voyage;
                    $sailScheduleModel->arrival_date = $value[$sail_schedule_arrival_dateIndex] ?: null;
                    $sailScheduleModel->ship_company_id = $ship_company_id;
                    $sailScheduleModel->save();
                    $sailScheduleMatchingModelsArray[] = $sailScheduleModel->toArray();
                    $sail_schedule_id = $sailScheduleModel->id;
                }

                $seaWaybillSaveArray['type'] = $value[$typeIndex];
                // 1. 每行type
                // 2. 启运港 目的港
                // 3. 文件名
                if (! $seaWaybillSaveArray['type']) {
                    if ($value[$mudigangIndex] === '湛江') {
                        $seaWaybillSaveArray['type'] = WuliuSeaWaybill::TYPE_JINKOU;
                    } elseif ($value[$qiyungangIndex] === '湛江') {
                        $seaWaybillSaveArray['type'] = WuliuSeaWaybill::TYPE_CHUKOU;
                    } else {
                        if ($type) {
                            $seaWaybillSaveArray['type'] = $type;
                        } else {
                            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '无法判断运单 进出口类型' . implode('_', $value));
                        }
                    }
                } else {
                    switch ($seaWaybillSaveArray['type']) {
                        case '进口':
                            $seaWaybillSaveArray['type'] = WuliuSeaWaybill::TYPE_JINKOU;
                            break;
                        case '出口':
                            $seaWaybillSaveArray['type'] = WuliuSeaWaybill::TYPE_CHUKOU;
                            break;
                        default:
                            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '无法判断运单 进出口类型:' . $seaWaybillSaveArray['type']);
                    }
                }
                $seaWaybillSaveArray['sail_schedule_id'] = $sail_schedule_id;
                if ($seaWaybillSaveArray['type'] === WuliuSeaWaybill::TYPE_JINKOU) {
                    $seaWaybillSaveArray['tos'] = WuliuSeaWaybill::TOS_DAOMEN;
                } else {
                    $seaWaybillSaveArray['tos'] = WuliuSeaWaybill::TOS_DEFAULT;
                }
                // $seaWaybillSaveArray['tos'] =  WuliuSeaWaybill::TOS_DAOMEN;

                $existsModelArray = WuliuSeaWaybill::getByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if ($existsModelArray) {
                    $seaWaybillSaveArray['id'] = $existsModelArray['id'];
                    if ($seaWaybillSaveArray['type'] === WuliuSeaWaybill::TYPE_JINKOU) {
                        # 存在的数据tos已经更新值了，所以继续使用值，不更新
                        if ($existsModelArray['tos'] !== WuliuSeaWaybill::TOS_DEFAULT) {
                            $seaWaybillSaveArray['tos'] = $existsModelArray['tos'];
                        }
                    }

                    $seaWaybillSaveArray['updated_at'] = Tools::getNowDate();
                    // unset($seaWaybillSaveArray['type'], $seaWaybillSaveArray['tos'], $seaWaybillSaveArray['updated_at']);
                    // $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex] ?: '';
                    // $seaWaybillSaveArray['ship_company_towing_fee'] = $value[$ship_company_towing_feeIndex] ?: 0;
                    $seaWaybillSaveArray['created_at'] = $value[$created_atIndex] ?: Tools::getNowDate();
                    $updateData[] = $seaWaybillSaveArray;
                    continue;
                    // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, $value[$numberIndex] . $value[$case_numberIndex] . '已存在');
                }
                $seaWaybillSaveArray['number'] = $value[$numberIndex];
                $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                switch ($value[$fh_statusIndex]) {
                    case '未放货':
                        $seaWaybillSaveArray['fh_status'] = WuliuSeaWaybill::FH_STATUS_NO;
                        break;
                    case '已放货':
                        $seaWaybillSaveArray['fh_status'] = WuliuSeaWaybill::FH_STATUS_YES;
                        break;
                    default:
                        $seaWaybillSaveArray['fh_status'] = WuliuSeaWaybill::FH_STATUS_DEFAULT;
                        break;
                }
                switch ($value[$receipt_statusIndex]) {
                    case '':
                    case '不需要':
                        $seaWaybillSaveArray['receipt_status'] = WuliuSeaWaybill::RECEIPT_STATUS_DEFAULT;
                        break;
                    case '需要':
                        $seaWaybillSaveArray['receipt_status'] = WuliuSeaWaybill::RECEIPT_STATUS_NOT_UPLOAD;
                        break;
                    default:
                        throw new ServiceException(ServiceCode::ERROR, [], 400, [], '签收单状态有误：' . $value[$receipt_statusIndex]);
                }

                $seaWaybillSaveArray['qf_number'] = $value[$qf_numberIndex];
                $seaWaybillSaveArray['weight'] = $value[$weightIndex] ?: '';
                $seaWaybillSaveArray['box'] = $value[$boxIndex] ?: '';
                $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex] ?: '';
                $seaWaybillSaveArray['ship_company_towing_fee'] = $value[$ship_company_towing_feeIndex] ?: 0;
                $seaWaybillSaveArray['liaison'] = $value[$liaison_Index] ?: '';
                $seaWaybillSaveArray['liaison_mobile'] = $value[$liaison_mobileIndex] ?: '';
                $seaWaybillSaveArray['liaison_address_detail'] = $value[$liaison_address_detailIndex] ?: '';
                $seaWaybillSaveArray['liaison_remark'] = $value[$liaison_remarkIndex] ?: '';
                $seaWaybillSaveArray['liaison_remark'] = $seaWaybillSaveArray['liaison_remark'] . $value[$remarkIndex] ?: '';
                $seaWaybillSaveArray['created_at'] = $value[$created_atIndex] ?: Tools::getNowDate();
                $insertData[] = $seaWaybillSaveArray;
            }

            $insertCount = 0;
            if ($insertData) {
                $insertCount = count($insertData);
                (new WuliuSeaWaybill())->insert($insertData);
            }

            $updateCount = 0;
            if ($updateData) {
                $updateCount = count($updateData);
                // (new WuliuSeaWaybill())->updateBatch($updateData);
            }

            Db::commit();

            return $this->resJson("共导入{$insertCount}条，更新{$updateCount}条数据！", [
                $insertData,
                $updateData,
            ]);
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 导入：中谷 晨鸣装纸.
     */
    public function importTemp1()
    {
        try {
            $file = $this->request->file('uploadFile');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        // var_dump($dataArray[0]);

        Db::beginTransaction();
        try {
            $ship_company_id = WuliuShipCompany::ZHONGGU;
            $car_finished_dateIndex = 0;
            $numberIndex = 1;
            $case_numberIndex = 2;
            $box_main_codeIndex = 3;
            $car_numberIndex = 4;
            $liaison_remarkIndex = 5;
            # 不加船期
            # box 可能为空
            # 备注
            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $carMatchingModels = WuliuCar::get();
            $carMatchingModelsArray = $carMatchingModels->toArray();
            $insertSeaWaybillArray = $existsNumberAndCaseNumberArray = [];
            foreach ($dataArray as $key => &$value) {
                // 运单号+箱号 存在，过滤
                $existsNumberAndCaseNumberStatus = WuliuSeaWaybill::checkIsExistsByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if ($existsNumberAndCaseNumberStatus) {
                    $existsNumberAndCaseNumberArray[] = [
                        'number' => $value[$numberIndex],
                        'case_number' => $value[$case_numberIndex],
                    ];
                    continue;
                }
                // car
                if ($value[$car_numberIndex]) {
                    // 有已对字眼，说明账单已对，先不理会
                    $numberTemp = str_replace('已对', '', $value[$car_numberIndex]);
                    $car_id = WuliuCar::getIdByNumber($carMatchingModelsArray, $numberTemp);
                    if (! $car_id) {
                        // 自动创建
                        $carModel = new WuliuCar();
                        $carModel->number = $numberTemp;
                        $carModel->save();
                        $carMatchingModelsArray[] = $carModel->toArray();
                        $value['car_id'] = $carModel->id;

                        // 不自动创建
                        // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "车牌号{{$numberTemp}}不存在，请先创建");
                    }
                    $value['car_id'] = $car_id;
                }
                if ($value[$car_finished_dateIndex]) {
                    $timeStr = str_replace('送', '', '2022.' . $value[$car_finished_dateIndex]);
                    $timeStr = str_replace('号', '日', $timeStr);
                    $timeStr = str_replace([
                        '号',
                        '日',
                        '提',
                    ], '', $timeStr);
                    // var_dump($timeStr);
                    $timeStr = str_replace([
                        '年',
                        '月',
                        '日',
                        '号',
                    ], '-', $timeStr);
                    $timeStr = str_replace('.', '-', $timeStr);
                    // var_dump($timeStr);
                    try {
                        $timestamp = strtotime($timeStr);
                        // var_dump($timestamp);
                        $value['car_finished_date'] = date('Y-m-d', $timestamp);
                    } catch (Exception $th) {
                        throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "车完成时间{$value[$car_finished_dateIndex]}格式有误，请更改后处理");
                    }
                }
                $seaWaybillSaveArray = [];
                $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
                $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'];
                $seaWaybillSaveArray['number'] = $value[$numberIndex];
                $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                $seaWaybillSaveArray['box_main_code'] = $value[$box_main_codeIndex] ?? '';
                $seaWaybillSaveArray['car_id'] = $value['car_id'];
                $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'];
                $seaWaybillSaveArray['liaison_remark'] = $value[$liaison_remarkIndex];
                $seaWaybillSaveArray['created_at'] = date('Y-m-d H:i:s');
                $insertSeaWaybillArray[] = $seaWaybillSaveArray;
                unset($seaWaybillSaveArray);
            }
            // var_dump($insertSeaWaybillArray);
            $numberCount = 0;
            if ($insertSeaWaybillArray) {
                WuliuSeaWaybill::insert($insertSeaWaybillArray);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
                $numberCount = count($insertSeaWaybillArray);
            }
            if ($existsNumberAndCaseNumberArray) {
                $text = '以下运单已存在：';
                foreach ($existsNumberAndCaseNumberArray as $key => $value) {
                    $text .= "[运单号：{$value['number']}-箱号：{$value['case_number']}] ";
                }
                return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！<br>{$text}");
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！");
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 导入：安通到港.
     */
    public function importTemp2()
    {
        try {
            $file = $this->request->file('uploadFile');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        // var_dump($dataArray[0]);

        Db::beginTransaction();
        try {
            $ship_company_id = WuliuShipCompany::ANTONG;
            $numberIndex = 1;
            $case_numberIndex = 2;
            $boxIndex = 3;
            $liaisonIndex = 4;
            $liaison_address_detailIndex = 5;
            $tosIndex = 6;
            $good_nameIndex = 7;
            $box_main_codeIndex = 8;
            $partner_nameIndex = 9;
            $liaison_remarkIndex = 10;

            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $carMatchingModels = WuliuCar::get();
            $carMatchingModelsArray = $carMatchingModels->toArray();
            $partnerMatchingModels = WuliuPartner::get();
            $partnerMatchingModelsArray = $partnerMatchingModels->toArray();

            $insertSeaWaybillArray = $existsNumberAndCaseNumberArray = [];
            foreach ($dataArray as $key => &$value) {
                // 运单号+箱号 存在，过滤
                $existsNumberAndCaseNumberStatus = WuliuSeaWaybill::checkIsExistsByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if ($existsNumberAndCaseNumberStatus) {
                    $existsNumberAndCaseNumberArray[] = [
                        'number' => $value[$numberIndex],
                        'case_number' => $value[$case_numberIndex],
                    ];
                    continue;
                }

                # 如果 合作公司name 不存在则创建
                $partner_id = WuliuPartner::getIdByName($partnerMatchingModelsArray, $value[$partner_nameIndex]);
                if (! $partner_id) {
                    $partnerModel = new WuliuPartner();
                    $partnerModel->name = $value[$partner_nameIndex];
                    $partnerModel->save();
                    $partnerMatchingModelsArray[] = $partnerModel->toArray();
                    $partner_id = $partnerModel->id;
                }
                $value['partner_id'] = $partner_id;

                $seaWaybillSaveArray = [];
                $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
                // $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'];
                $seaWaybillSaveArray['number'] = $value[$numberIndex];
                $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                $seaWaybillSaveArray['box'] = $value[$boxIndex] ?? '';
                $seaWaybillSaveArray['box_main_code'] = $value[$box_main_codeIndex] ?? '';
                // $seaWaybillSaveArray['car_id'] = $value['car_id'];
                // $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'];
                $seaWaybillSaveArray['liaison'] = $value[$liaisonIndex];
                $seaWaybillSaveArray['liaison_address_detail'] = $value[$liaison_address_detailIndex];
                $seaWaybillSaveArray['liaison_remark'] = $value[$liaison_remarkIndex];
                $seaWaybillSaveArray['tos'] = $value[$tosIndex];
                $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex];
                $seaWaybillSaveArray['partner_bill_id'] = $value['partner_id'];
                # 代理
                $seaWaybillSaveArray['created_at'] = date('Y-m-d H:i:s');
                $insertSeaWaybillArray[] = $seaWaybillSaveArray;
                unset($seaWaybillSaveArray);
            }
            // var_dump($insertSeaWaybillArray);
            $numberCount = 0;
            if ($insertSeaWaybillArray) {
                WuliuSeaWaybill::insert($insertSeaWaybillArray);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
                $numberCount = count($insertSeaWaybillArray);
            }
            if ($existsNumberAndCaseNumberArray) {
                $text = '以下运单已存在：';
                foreach ($existsNumberAndCaseNumberArray as $key => $value) {
                    $text .= "[运单号：{$value['number']}-箱号：{$value['case_number']}] ";
                }
                return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！<br>{$text}");
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！");
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 导入：.
     */
    public function importZhongguangshimo2022()
    {
        try {
            $file = $this->request->file('uploadFile');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        // var_dump($dataArray[0]);

        Db::beginTransaction();
        try {
            // $ship_company_id = WuliuShipCompany::ANTONG;
            $numberIndex = 1;
            $case_numberIndex = 2;
            $liaison_address_detailIndex = 3;
            $boxIndex = 4;
            $good_nameIndex = 5;
            $ship_company_towing_feeIndex = 6;
            $car_idANDcar_finished_dateIndex = 7;
            // $liaisonIndex = 4;
            // $tosIndex = 6;
            // $box_main_codeIndex = 8;
            // $partner_nameIndex = 9;
            // $liaison_remarkIndex = 10;

            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $carMatchingModels = WuliuCar::get();
            $carMatchingModelsArray = $carMatchingModels->toArray();
            $partnerMatchingModels = WuliuPartner::get();
            $partnerMatchingModelsArray = $partnerMatchingModels->toArray();

            $insertSeaWaybillArray = $existsNumberAndCaseNumberArray = [];
            foreach ($dataArray as $key => &$value) {
                // 运单号+箱号 存在，过滤
                $existsNumberAndCaseNumberStatus = WuliuSeaWaybill::checkIsExistsByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if ($existsNumberAndCaseNumberStatus) {
                    $existsNumberAndCaseNumberArray[] = [
                        'number' => $value[$numberIndex],
                        'case_number' => $value[$case_numberIndex],
                    ];
                    continue;
                }

                # 如果 合作公司name 不存在则创建
                $partner_id = WuliuPartner::getIdByName($partnerMatchingModelsArray, '广东中光国际货运代理有限公司');
                if (! $partner_id) {
                    $partnerModel = new WuliuPartner();
                    $partnerModel->name = '广东中光国际货运代理有限公司';
                    $partnerModel->save();
                    $partnerMatchingModelsArray[] = $partnerModel->toArray();
                    $partner_id = $partnerModel->id;
                }
                $value['partner_id'] = $partner_id;

                # 车辆 + 派送日期
                if ($value[$car_idANDcar_finished_dateIndex]) {
                    // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, $value[$car_idANDcar_finished_dateIndex]);
                    // var_dump($value[$car_idANDcar_finished_dateIndex]);
                    $value[$car_idANDcar_finished_dateIndex] = str_replace("\t", ' ', $value[$car_idANDcar_finished_dateIndex]);
                    $value[$car_idANDcar_finished_dateIndex] = str_replace('  ', ' ', $value[$car_idANDcar_finished_dateIndex]);
                    $value[$car_idANDcar_finished_dateIndex] = str_replace('   ', ' ', $value[$car_idANDcar_finished_dateIndex]);
                    $array = explode(' ', $value[$car_idANDcar_finished_dateIndex]);
                    if (count($array) != 2) {
                        return $this->responseJson(ServiceCode::SUCCESS, $value[$car_idANDcar_finished_dateIndex]);
                    }
                    $car_number = $array[0];
                    $car_finished_date = $array[1];
                    $timeStr = str_replace([
                        '送',
                        '已',
                        '对',
                    ], '', '2022.' . $car_finished_date);
                    $timeStr = str_replace('号', '日', $timeStr);
                    $timeStr = str_replace([
                        '号',
                        '日',
                        '提',
                    ], '', $timeStr);
                    // var_dump($timeStr);
                    $timeStr = str_replace([
                        '年',
                        '月',
                        '日',
                        '号',
                    ], '-', $timeStr);
                    $timeStr = str_replace('.', '-', $timeStr);
                    if (! $timestamp = strtotime($timeStr)) {
                        return $this->responseJson(ServiceCode::SUCCESS, $car_finished_date);
                    }
                    // var_dump($timeStr);
                    // var_dump($timestamp);
                    $value['car_finished_date'] = date('Y-m-d', $timestamp);

                    $car_id = WuliuCar::getIdByNumber($carMatchingModelsArray, $car_number);
                    if (! $car_id) {
                        // 自动创建
                        $carModel = new WuliuCar();
                        $carModel->number = $car_number;
                        $carModel->save();
                        $carMatchingModelsArray[] = $carModel->toArray();
                        $value['car_id'] = $carModel->id;

                        // 不自动创建
                        // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "车牌号{{$numberTemp}}不存在，请先创建");
                    }
                    $value['car_id'] = $car_id;
                }

                $seaWaybillSaveArray = [];
                // $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
                // $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'];
                $seaWaybillSaveArray['number'] = $value[$numberIndex];
                $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                $seaWaybillSaveArray['ship_company_towing_fee'] = $value[$ship_company_towing_feeIndex];
                $seaWaybillSaveArray['box'] = $value[$boxIndex];
                // $seaWaybillSaveArray['box_main_code'] = $value[$box_main_codeIndex] ?? '';
                $seaWaybillSaveArray['car_id'] = $value['car_id'];
                $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'];
                // $seaWaybillSaveArray['liaison'] = $value[$liaisonIndex];
                $seaWaybillSaveArray['liaison_address_detail'] = $value[$liaison_address_detailIndex];
                // $seaWaybillSaveArray['liaison_remark'] = $value[$liaison_remarkIndex];
                // $seaWaybillSaveArray['tos'] = $value[$tosIndex];
                $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex];
                $seaWaybillSaveArray['partner_bill_id'] = $value['partner_id'];
                # 代理
                $seaWaybillSaveArray['created_at'] = date('Y-m-d H:i:s');
                $insertSeaWaybillArray[] = $seaWaybillSaveArray;
                unset($seaWaybillSaveArray);
            }
            // var_dump($insertSeaWaybillArray);
            $numberCount = 0;
            if ($insertSeaWaybillArray) {
                WuliuSeaWaybill::insert($insertSeaWaybillArray);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
                $numberCount = count($insertSeaWaybillArray);
            }
            if ($existsNumberAndCaseNumberArray) {
                $text = '以下运单已存在：';
                foreach ($existsNumberAndCaseNumberArray as $key => $value) {
                    $text .= "[运单号：{$value['number']}-箱号：{$value['case_number']}] ";
                }
                return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！<br>{$text}");
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！");
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 导入：.
     */
    public function importGuanjian20212022()
    {
        try {
            $file = $this->request->file('uploadFile');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        // var_dump($dataArray[0]);

        Db::beginTransaction();
        try {
            // $ship_company_id = WuliuShipCompany::ANTONG;
            $numberIndex = 1;
            $case_numberIndex = 2;
            $qf_numberIndex = 3;
            $good_nameIndex = 4;
            $boxIndex = 5;
            $ship_company_towing_feeIndex = 6;
            $liaison_address_detailIndex = 7;
            $car_numberIndex = 8;
            $liaison_address_detailIndex2 = 9;
            $liaison_address_detailIndex3 = 10;
            // $liaisonIndex = 4;
            // $tosIndex = 6;
            // $box_main_codeIndex = 8;
            // $partner_nameIndex = 9;
            // $liaison_remarkIndex = 10;

            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $carMatchingModels = WuliuCar::get();
            $carMatchingModelsArray = $carMatchingModels->toArray();
            $partnerMatchingModels = WuliuPartner::get();
            $partnerMatchingModelsArray = $partnerMatchingModels->toArray();

            $insertSeaWaybillArray = $existsNumberAndCaseNumberArray = [];
            foreach ($dataArray as $key => &$value) {
                if ($key === 0) {
                    continue;
                }
                // 运单号+箱号 存在，过滤
                $existsNumberAndCaseNumberStatus = WuliuSeaWaybill::checkIsExistsByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if ($existsNumberAndCaseNumberStatus) {
                    $existsNumberAndCaseNumberArray[] = [
                        'number' => $value[$numberIndex],
                        'case_number' => $value[$case_numberIndex],
                    ];
                    continue;
                }

                # 如果 合作公司name 不存在则创建
                // $partner_id = WuliuPartner::getIdByName($partnerMatchingModelsArray, '广东中光国际货运代理有限公司');
                // if (! $partner_id) {
                //     $partnerModel = new WuliuPartner();
                //     $partnerModel->name = '广东中光国际货运代理有限公司';
                //     $partnerModel->save();
                //     $partnerMatchingModelsArray[] = $partnerModel->toArray();
                //     $partner_id = $partnerModel->id;
                // }
                // $value['partner_id'] = $partner_id;

                # 车辆 + 派送日期
                // if ($value[$car_idANDcar_finished_dateIndex]) {
                //     // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, $value[$car_idANDcar_finished_dateIndex]);
                //     // var_dump($value[$car_idANDcar_finished_dateIndex]);
                //     $value[$car_idANDcar_finished_dateIndex] = str_replace("\t", ' ', $value[$car_idANDcar_finished_dateIndex]);
                //     $value[$car_idANDcar_finished_dateIndex] = str_replace('  ', ' ', $value[$car_idANDcar_finished_dateIndex]);
                //     $value[$car_idANDcar_finished_dateIndex] = str_replace('   ', ' ', $value[$car_idANDcar_finished_dateIndex]);
                //     $array = explode(' ', $value[$car_idANDcar_finished_dateIndex]);
                //     if (count($array) != 2) {
                //         return $this->responseJson(ServiceCode::SUCCESS, $value[$car_idANDcar_finished_dateIndex]);
                //     }
                //     $car_number = $array[0];
                //     $car_finished_date = $array[1];
                //     $timeStr = str_replace(['送', '已', '对'], '', '2022.' . $car_finished_date);
                //     $timeStr = str_replace('号', '日', $timeStr);
                //     $timeStr = str_replace(['号', '日', '提'], '', $timeStr);
                //     // var_dump($timeStr);
                //     $timeStr = str_replace(['年', '月', '日', '号'], '-', $timeStr);
                //     $timeStr = str_replace('.', '-', $timeStr);
                //     if (! $timestamp = strtotime($timeStr)) {
                //         return $this->responseJson(ServiceCode::SUCCESS, $car_finished_date);
                //     }
                //     // var_dump($timeStr);
                //     // var_dump($timestamp);
                //     $value['car_finished_date'] = date('Y-m-d H:i:s', $timestamp);
                // }
                // $car_number = $value[$car_numberIndex];
                $car_number = str_replace('已对', '', $value[$car_numberIndex]);
                $car_id = WuliuCar::getIdByNumber($carMatchingModelsArray, $car_number);
                if (! $car_id) {
                    // 自动创建
                    $carModel = new WuliuCar();
                    $carModel->number = $car_number;
                    $carModel->save();
                    $carMatchingModelsArray[] = $carModel->toArray();
                    $value['car_id'] = $carModel->id;

                    // 不自动创建
                    // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "车牌号{{$numberTemp}}不存在，请先创建");
                }
                $value['car_id'] = $car_id;

                $seaWaybillSaveArray = [];
                // $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
                // $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'];
                $seaWaybillSaveArray['number'] = $value[$numberIndex];
                $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                $seaWaybillSaveArray['qf_number'] = $value[$qf_numberIndex];
                $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex];
                $seaWaybillSaveArray['box'] = $value[$boxIndex];
                $seaWaybillSaveArray['ship_company_towing_fee'] = $value[$ship_company_towing_feeIndex];
                // $seaWaybillSaveArray['box_main_code'] = $value[$box_main_codeIndex] ?? '';
                $seaWaybillSaveArray['car_id'] = $value['car_id'];
                // $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'];
                // $seaWaybillSaveArray['liaison'] = $value[$liaisonIndex];
                $seaWaybillSaveArray['liaison_address_detail'] = $value[$liaison_address_detailIndex] . '--' . $value[$liaison_address_detailIndex2] . '--' . $value[$liaison_address_detailIndex3];
                // $seaWaybillSaveArray['liaison_remark'] = $value[$liaison_remarkIndex];
                // $seaWaybillSaveArray['tos'] = $value[$tosIndex];
                // $seaWaybillSaveArray['partner_bill_id'] = $value['partner_id'];
                # 代理
                $seaWaybillSaveArray['created_at'] = date('Y-m-d H:i:s');
                $insertSeaWaybillArray[] = $seaWaybillSaveArray;
                unset($seaWaybillSaveArray);
            }
            // var_dump($insertSeaWaybillArray);
            $numberCount = 0;
            if ($insertSeaWaybillArray) {
                WuliuSeaWaybill::insert($insertSeaWaybillArray);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
                $numberCount = count($insertSeaWaybillArray);
            }
            if ($existsNumberAndCaseNumberArray) {
                $text = '以下运单已存在：';
                foreach ($existsNumberAndCaseNumberArray as $key => $value) {
                    $text .= "[运单号：{$value['number']}-箱号：{$value['case_number']}] ";
                }
                return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！<br>{$text}");
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！");
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 导入：Z环保增塑剂2022年 海航.
     */
    public function importHaihang2022()
    {
        try {
            $file = $this->request->file('uploadFile');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        // var_dump($dataArray[0]);

        Db::beginTransaction();
        try {
            // $ship_company_id = WuliuShipCompany::ANTONG;
            $numberIndex = 1;
            $case_numberIndex = 2;
            $qf_numberIndex = 3;
            $good_nameIndex = 4;
            $weightIndex = 5;
            $liaison_address_detailIndex = 6;

            // $boxIndex = 5;
            $ship_company_towing_feeIndex = 8;
            $car_numberIndex = 13;
            $car_finished_dateIndex = 14;
            // $liaison_address_detailIndex2 = 9;
            // $liaison_address_detailIndex3 = 10;
            // $liaisonIndex = 4;
            // $tosIndex = 6;
            // $box_main_codeIndex = 8;
            // $partner_nameIndex = 9;
            // $liaison_remarkIndex = 10;

            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $carMatchingModels = WuliuCar::get();
            $carMatchingModelsArray = $carMatchingModels->toArray();
            $partnerMatchingModels = WuliuPartner::get();
            $partnerMatchingModelsArray = $partnerMatchingModels->toArray();

            $insertSeaWaybillArray = $existsNumberAndCaseNumberArray = [];
            foreach ($dataArray as $key => &$value) {
                if ($key === 0) {
                    continue;
                }
                // 运单号+箱号 存在，过滤
                $existsNumberAndCaseNumberStatus = WuliuSeaWaybill::checkIsExistsByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if ($existsNumberAndCaseNumberStatus) {
                    $existsNumberAndCaseNumberArray[] = [
                        'number' => $value[$numberIndex],
                        'case_number' => $value[$case_numberIndex],
                    ];
                    continue;
                }

                # 如果 合作公司name 不存在则创建
                $partner_name = '青岛东方海航货运有限公司';
                $partner_id = WuliuPartner::getIdByName($partnerMatchingModelsArray, $partner_name);
                if (! $partner_id) {
                    $partnerModel = new WuliuPartner();
                    $partnerModel->name = $partner_name;
                    $partnerModel->save();
                    $partnerMatchingModelsArray[] = $partnerModel->toArray();
                    $partner_id = $partnerModel->id;
                }
                $value['partner_id'] = $partner_id;

                # 车辆 + 派送日期
                if ($value[$car_finished_dateIndex]) {
                    // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, $value[$car_idANDcar_finished_dateIndex]);
                    // var_dump($value[$car_idANDcar_finished_dateIndex]);
                    $value[$car_finished_dateIndex] = str_replace("\t", ' ', $value[$car_finished_dateIndex]);
                    $value[$car_finished_dateIndex] = str_replace('  ', ' ', $value[$car_finished_dateIndex]);
                    $value[$car_finished_dateIndex] = str_replace('   ', ' ', $value[$car_finished_dateIndex]);
                    // $array = explode(' ', $value[$car_finished_dateIndex]);
                    // if (count($array) != 2) {
                    //     return $this->responseJson(ServiceCode::SUCCESS, $value[$car_finished_dateIndex]);
                    // }
                    // $car_number = $array[0];
                    // $car_finished_date = $array[1];
                    $timeStr = str_replace([
                        '送',
                        '已',
                        '对',
                    ], '', '2022.' . $value[$car_finished_dateIndex]);
                    $timeStr = str_replace('号', '日', $timeStr);
                    $timeStr = str_replace([
                        '号',
                        '日',
                        '提',
                    ], '', $timeStr);
                    // var_dump($timeStr);
                    $timeStr = str_replace([
                        '年',
                        '月',
                        '日',
                        '号',
                    ], '-', $timeStr);
                    $timeStr = str_replace('.', '-', $timeStr);
                    if (! $timestamp = strtotime($timeStr)) {
                        return $this->responseJson(ServiceCode::SUCCESS, $value[$car_finished_dateIndex]);
                    }
                    // var_dump($timeStr);
                    // var_dump($timestamp);
                    $value['car_finished_date'] = date('Y-m-d', $timestamp);
                }

                if ($value[$car_numberIndex]) {
                    $car_number = str_replace([
                        '已对',
                        ' ',
                    ], '', $value[$car_numberIndex]);
                    $car_id = WuliuCar::getIdByNumber($carMatchingModelsArray, $car_number);
                    if (! $car_id) {
                        // 自动创建
                        $carModel = new WuliuCar();
                        $carModel->number = $car_number;
                        $carModel->save();
                        $carMatchingModelsArray[] = $carModel->toArray();
                        $value['car_id'] = $carModel->id;

                        // 不自动创建
                        // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "车牌号{{$numberTemp}}不存在，请先创建");
                    }
                    $value['car_id'] = $car_id;
                }

                $seaWaybillSaveArray = [];
                // $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
                // $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'];
                $seaWaybillSaveArray['number'] = $value[$numberIndex];
                $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                $seaWaybillSaveArray['qf_number'] = $value[$qf_numberIndex];
                $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex];
                // $seaWaybillSaveArray['box'] = $value[$boxIndex];
                // if (! is_numeric($value[$weightIndex])) {
                //     return $this->responseJson(ServiceCode::SUCCESS, $value[$weightIndex]);
                // }
                $seaWaybillSaveArray['weight'] = $value[$weightIndex] ?? null;
                // $seaWaybillSaveArray['ship_company_towing_fee'] = $value[$ship_company_towing_feeIndex];
                // $seaWaybillSaveArray['box_main_code'] = $value[$box_main_codeIndex] ?? '';
                $seaWaybillSaveArray['car_id'] = $value['car_id'] ?? null;
                // $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'];
                // $seaWaybillSaveArray['liaison'] = $value[$liaisonIndex];
                $seaWaybillSaveArray['liaison_address_detail'] = $value[$liaison_address_detailIndex];
                // $seaWaybillSaveArray['liaison_remark'] = $value[$liaison_remarkIndex];
                // $seaWaybillSaveArray['tos'] = $value[$tosIndex];
                // $seaWaybillSaveArray['partner_bill_id'] = $value['partner_id'];
                # 代理
                $seaWaybillSaveArray['created_at'] = date('Y-m-d H:i:s');
                $insertSeaWaybillArray[] = $seaWaybillSaveArray;
                unset($seaWaybillSaveArray);
            }
            // var_dump($insertSeaWaybillArray);
            $numberCount = 0;
            if ($insertSeaWaybillArray) {
                WuliuSeaWaybill::insert($insertSeaWaybillArray);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
                $numberCount = count($insertSeaWaybillArray);
            }
            if ($existsNumberAndCaseNumberArray) {
                $text = '以下运单已存在：';
                foreach ($existsNumberAndCaseNumberArray as $key => $value) {
                    $text .= "[运单号：{$value['number']}-箱号：{$value['case_number']}] ";
                }

                Db::commit();
                return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！<br>{$text}");
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！");
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function importNewCommon()
    {
        try {
            $file = $this->request->file('uploadFile');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        // var_dump($dataArray[0]);

        Db::beginTransaction();
        try {
            // $ship_company_id = WuliuShipCompany::ANTONG;
            $partner_nameIndex = 0;
            $numberIndex = 1;
            $case_numberIndex = 2;
            $qf_numberIndex = 3;
            $liaison_address_detailIndex = 4;
            $boxIndex = 5;
            $good_nameIndex = 6;
            $ship_company_towing_feeIndex = 7;
            $car_numberInfo = 8;
            $liaison_address_detailIndex2 = 9;
            $liaison_address_detailIndex3 = 10;

            // $car_numberIndex = 13;
            // $weightIndex = 5;

            // $boxIndex = 5;
            // $car_finished_dateIndex = 14;
            // $liaison_address_detailIndex2 = 9;
            // $liaison_address_detailIndex3 = 10;
            // $liaisonIndex = 4;
            // $tosIndex = 6;
            // $box_main_codeIndex = 8;
            // $partner_nameIndex = 9;
            // $liaison_remarkIndex = 10;

            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $carMatchingModels = WuliuCar::get();
            $carMatchingModelsArray = $carMatchingModels->toArray();
            $partnerMatchingModels = WuliuPartner::get();
            $partnerMatchingModelsArray = $partnerMatchingModels->toArray();

            $insertSeaWaybillArray = $existsNumberAndCaseNumberArray = [];
            foreach ($dataArray as $key => &$value) {
                if ($key === 0) {
                    continue;
                }
                // 运单号+箱号 存在，过滤
                $existsNumberAndCaseNumberStatus = WuliuSeaWaybill::checkIsExistsByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if ($existsNumberAndCaseNumberStatus) {
                    $existsNumberAndCaseNumberArray[] = [
                        'number' => $value[$numberIndex],
                        'case_number' => $value[$case_numberIndex],
                    ];
                    continue;
                }

                # 如果 合作公司name 不存在则创建
                $partner_id = WuliuPartner::getIdByName($partnerMatchingModelsArray, $value[$partner_nameIndex]);
                if (! $partner_id) {
                    $partnerModel = new WuliuPartner();
                    $partnerModel->name = $value[$partner_nameIndex];
                    $partnerModel->save();
                    $partnerMatchingModelsArray[] = $partnerModel->toArray();
                    $partner_id = $partnerModel->id;
                }
                $value['partner_id'] = $partner_id;

                # 车辆 + 派送日期
                $a0 = $value[$car_numberInfo];
                if ($value[$car_numberInfo]) {
                    if (is_string($value[$liaison_address_detailIndex2]) && $value[$liaison_address_detailIndex2]) {
                        if (strpos($value[$liaison_address_detailIndex2], '日') || strpos($value[$liaison_address_detailIndex2], '号') || strpos($value[$liaison_address_detailIndex2], '送')) {
                            if (strpos($value[$liaison_address_detailIndex2], '订单号') === false
                                && strpos($value[$liaison_address_detailIndex2], '陈日攀') === false
                                && strpos($value[$liaison_address_detailIndex2], '发货方日鑫鱼粉厂') === false
                                && strpos($value[$liaison_address_detailIndex2], '4383送') === false
                            ) {
                                $value[$car_numberInfo] .= ' ' . $value[$liaison_address_detailIndex2];
                                $value[$liaison_address_detailIndex2] = $value[$liaison_address_detailIndex3];
                            }
                        }
                    }

                    // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, $value[$car_idANDcar_finished_dateIndex]);
                    // var_dump($value[$car_idANDcar_finished_dateIndex]);
                    $value[$car_numberInfo] = str_replace("\t", ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('     ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('    ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('   ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('  ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace([
                        '送',
                        '有地',
                        ' 忆',
                        '忆',
                        ' 已',
                        '已',
                        '对',
                        '四',
                        '哥',
                        '已對',
                        '付',
                        '收',
                        '装',
                    ], '', $value[$car_numberInfo]);
                    $array = explode(' ', $value[$car_numberInfo]);
                    if (count($array) > 2) {
                        // var_dump($value[$car_numberInfo], $value[$liaison_address_detailIndex2]);
                        return $this->responseJson(ServiceCode::SUCCESS, $value[$car_numberInfo]);
                    }
                    $car_number = $array[0];
                    $car_id = WuliuCar::getIdByNumber($carMatchingModelsArray, $car_number);
                    if (! $car_id) {
                        // 自动创建
                        $carModel = new WuliuCar();
                        $carModel->number = $car_number;
                        $carModel->save();
                        $carMatchingModelsArray[] = $carModel->toArray();
                        $value['car_id'] = $carModel->id;

                        // 不自动创建
                        // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "车牌号{{$numberTemp}}不存在，请先创建");
                    }
                    $value['car_id'] = $car_id;

                    if (isset($array[1])) {
                        $car_finished_date = trim($array[1]);
                        $timeStr = str_replace([
                            '送',
                            "\t",
                            '发货方日鑫鱼粉厂',
                            '有地',
                            ' 忆',
                            '忆',
                            '已',
                            '对',
                            '對',
                            '阿海',
                        ], '', '2022.' . $car_finished_date);
                        $a1 = $timeStr;
                        $timeStr = str_replace('号', '日', $timeStr);
                        $a2 = $timeStr;
                        $timeStr = str_replace([
                            '号',
                            '日',
                            '提',
                        ], '', $timeStr);
                        $a3 = $timeStr;
                        // var_dump($timeStr);
                        $timeStr = str_replace([
                            '年',
                            '月',
                            '日',
                            '号',
                        ], '-', $timeStr);
                        $a4 = $timeStr;
                        $timeStr = str_replace('.', '-', $timeStr);
                        $a5 = $timeStr;

                        if (! $timestamp = strtotime($timeStr)) {
                            return $this->responseJson(ServiceCode::SUCCESS, $a1 . $a2 . $a3 . $a4 . $a5 . '---' . $car_finished_date, $a0);
                        }
                        // var_dump($timeStr);
                        // var_dump($timestamp);
                        $value['car_finished_date'] = date('Y-m-d', $timestamp);
                    }
                }

                $seaWaybillSaveArray = [];
                // $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
                // $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'];
                $seaWaybillSaveArray['number'] = $value[$numberIndex];
                $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                $seaWaybillSaveArray['qf_number'] = $value[$qf_numberIndex];
                $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex];
                $seaWaybillSaveArray['box'] = $value[$boxIndex];
                // if (! is_numeric($value[$weightIndex])) {
                //     return $this->responseJson(ServiceCode::SUCCESS, $value[$weightIndex]);
                // }
                // $seaWaybillSaveArray['weight'] = $value[$weightIndex] ?? null;
                $seaWaybillSaveArray['ship_company_towing_fee'] = $value[$ship_company_towing_feeIndex];
                // $seaWaybillSaveArray['box_main_code'] = $value[$box_main_codeIndex] ?? '';
                $seaWaybillSaveArray['car_id'] = $value['car_id'] ?? null;
                $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'] ?? null;

                // $seaWaybillSaveArray['liaison'] = $value[$liaisonIndex];
                $seaWaybillSaveArray['liaison_address_detail'] = $value[$liaison_address_detailIndex] . '--' . $value[$liaison_address_detailIndex2] . '--' . $value[$liaison_address_detailIndex3];
                // $seaWaybillSaveArray['liaison_remark'] = $value[$liaison_remarkIndex];
                // $seaWaybillSaveArray['tos'] = $value[$tosIndex];
                // $seaWaybillSaveArray['partner_bill_id'] = $value['partner_id'];
                # 代理
                $seaWaybillSaveArray['created_at'] = date('Y-m-d H:i:s');
                $insertSeaWaybillArray[] = $seaWaybillSaveArray;
                unset($seaWaybillSaveArray);
            }
            // var_dump($insertSeaWaybillArray);
            $numberCount = 0;
            if ($insertSeaWaybillArray) {
                WuliuSeaWaybill::insert($insertSeaWaybillArray);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
                $numberCount = count($insertSeaWaybillArray);
            }
            if ($existsNumberAndCaseNumberArray) {
                $text = '以下运单已存在：';
                foreach ($existsNumberAndCaseNumberArray as $key => $value) {
                    $text .= "[运单号：{$value['number']}-箱号：{$value['case_number']}] ";
                }

                Db::commit();
                return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！<br>{$text}");
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！");
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function importNewCommonAddPartnerId()
    {
        try {
            $file = $this->request->file('uploadFile');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        // var_dump($dataArray[0]);

        Db::beginTransaction();
        try {
            // $ship_company_id = WuliuShipCompany::ANTONG;
            $partner_nameIndex = 0;
            $numberIndex = 1;
            $case_numberIndex = 2;
            $partner_towing_feeIndex = 7;

            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            // $carMatchingModels = WuliuCar::get();
            // $carMatchingModelsArray = $carMatchingModels->toArray();
            $partnerMatchingModels = WuliuPartner::get();
            $partnerMatchingModelsArray = $partnerMatchingModels->toArray();

            $updateSeaWaybillArray = $errorArray = [];
            foreach ($dataArray as $key => &$value) {
                if ($key === 0) {
                    continue;
                }
                // 运单号+箱号 存在，过滤
                $modelArray = WuliuSeaWaybill::getByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if (! $modelArray) {
                    // $errorArray[] = [
                    // 'number' => $value[$numberIndex],
                    // 'case_number' => $value[$case_numberIndex],
                    // ];
                    continue;
                }

                # 如果 合作公司name 不存在则创建
                $partner_id = WuliuPartner::getIdByName($partnerMatchingModelsArray, $value[$partner_nameIndex]);
                if (! $partner_id) {
                    $partnerModel = new WuliuPartner();
                    $partnerModel->name = $value[$partner_nameIndex];
                    $partnerModel->save();
                    $partnerMatchingModelsArray[] = $partnerModel->toArray();
                    $partner_id = $partnerModel->id;
                }
                $value['partner_id'] = $partner_id;

                $seaWaybillSaveArray = [];
                $seaWaybillSaveArray['id'] = $modelArray['id'];
                $seaWaybillSaveArray['partner_id'] = $value['partner_id'];
                $seaWaybillSaveArray['partner_towing_fee'] = $value[$partner_towing_feeIndex];
                $seaWaybillSaveArray['tos'] = WuliuSeaWaybill::TOS_DAOGANG;
                # 代理

                $updateSeaWaybillArray[] = $seaWaybillSaveArray;
                unset($seaWaybillSaveArray);
            }
            $numberCount = count($updateSeaWaybillArray);
            (new WuliuSeaWaybill())->updateBatch($updateSeaWaybillArray);
            Db::commit();
            return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！");
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 导入从安通系统中进口的所有运单excel数据.
     */
    public function importAntongJinkou()
    {
        try {
            $file = $this->request->file('uploadFile');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        // var_dump($dataArray[0]);

        Db::beginTransaction();
        try {
            $ship_company_id = WuliuShipCompany::ANTONG;
            $sail_scheduleNameIndex = 0;
            $sail_scheduleVoyageIndex = 1;
            $numberIndex = 4;
            $case_numberIndex = 5;
            $boxIndex = 7;
            $qf_numberIndex = 11;
            $good_nameIndex = 12;
            $ship_company_towing_feeIndex = 14;
            $liaison_address_detailIndex = 16;
            $liaison_address_detailIndex2 = 17;
            // $partner_nameIndex = 0;
            // $car_numberInfo = 8;
            // $liaison_address_detailIndex3 = 10;

            // $car_numberIndex = 13;
            // $weightIndex = 5;

            // $boxIndex = 5;
            // $car_finished_dateIndex = 14;
            // $liaison_address_detailIndex2 = 9;
            // $liaison_address_detailIndex3 = 10;
            // $liaisonIndex = 4;
            // $tosIndex = 6;
            // $box_main_codeIndex = 8;
            // $partner_nameIndex = 9;
            // $liaison_remarkIndex = 10;

            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $sailScheduleMatchingModels = WuliuSailSchedule::get();
            $sailScheduleMatchingModelsArray = $sailScheduleMatchingModels->toArray();
            $carMatchingModels = WuliuCar::get();
            $carMatchingModelsArray = $carMatchingModels->toArray();
            // $partnerMatchingModels = WuliuPartner::get();
            // $partnerMatchingModelsArray = $partnerMatchingModels->toArray();

            $insertSeaWaybillArray = $existsNumberAndCaseNumberArray = [];
            foreach ($dataArray as $key => &$value) {
                if ($key === 0) {
                    continue;
                }
                // 船期
                if (! $value[$sail_scheduleNameIndex] || ! $value[$sail_scheduleVoyageIndex]) {
                    throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '请先在表格中填写每一个船期信息');
                }
                $sail_schedule_id = WuliuSailSchedule::getIdByShipCompanyNameAndNameAndVoyage($sailScheduleMatchingModelsArray, $ship_company_id, $value[$sail_scheduleNameIndex], $value[$sail_scheduleVoyageIndex]);
                // var_dump($sail_schedule_id);
                // return $this->responseJson(ServiceCode::SUCCESS, 1);
                // return $this->responseJson(ServiceCode::SUCCESS, [$sailScheduleMatchingModelsArray, $sail_schedule_id, $ship_company_id, $sail_schedule_array[0], $sail_schedule_array[1]]);
                // var_dump();
                if (! $sail_schedule_id) {
                    $sailScheduleModel = new WuliuSailSchedule();
                    $sailScheduleModel->name = $value[$sail_scheduleNameIndex];
                    $sailScheduleModel->voyage = $value[$sail_scheduleVoyageIndex];
                    $sailScheduleModel->ship_company_id = $ship_company_id;
                    $sailScheduleModel->save();
                    $sailScheduleMatchingModelsArray[] = $sailScheduleModel->toArray();
                    $sail_schedule_id = $sailScheduleModel->id;
                }

                // 运单号+箱号 存在，过滤
                $existsNumberAndCaseNumberStatus = WuliuSeaWaybill::checkIsExistsByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if ($existsNumberAndCaseNumberStatus) {
                    $existsNumberAndCaseNumberArray[] = [
                        'number' => $value[$numberIndex],
                        'case_number' => $value[$case_numberIndex],
                    ];
                    continue;
                }

                $seaWaybillSaveArray = [];
                $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
                $seaWaybillSaveArray['sail_schedule_id'] = $sail_schedule_id;
                $seaWaybillSaveArray['number'] = $value[$numberIndex];
                $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                $seaWaybillSaveArray['box'] = $value[$boxIndex];
                $seaWaybillSaveArray['qf_number'] = $value[$qf_numberIndex];
                $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex];
                $seaWaybillSaveArray['ship_company_towing_fee'] = $value[$ship_company_towing_feeIndex];
                $seaWaybillSaveArray['type'] = WuliuSeaWaybill::TYPE_JINKOU;
                $seaWaybillSaveArray['liaison_address_detail'] = $value[$liaison_address_detailIndex] . '--' . $value[$liaison_address_detailIndex2];
                # 代理
                $seaWaybillSaveArray['created_at'] = date('Y-m-d H:i:s');
                $insertSeaWaybillArray[] = $seaWaybillSaveArray;
                unset($seaWaybillSaveArray);
            }
            // var_dump($insertSeaWaybillArray);
            $numberCount = 0;
            if ($insertSeaWaybillArray) {
                WuliuSeaWaybill::insert($insertSeaWaybillArray);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
                $numberCount = count($insertSeaWaybillArray);
            }
            if ($existsNumberAndCaseNumberArray) {
                $text = '以下运单已存在：';
                foreach ($existsNumberAndCaseNumberArray as $key => $value) {
                    $text .= "[运单号：{$value['number']}-箱号：{$value['case_number']}] ";
                }

                Db::commit();
                return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！<br>{$text}");
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！");
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 导入从中谷系统中特殊派车出口的所有运单excel数据.
     */
    public function importZhongguTeshupaicheChukou()
    {
        try {
            $file = $this->request->file('uploadFile');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        // var_dump($dataArray[0]);

        Db::beginTransaction();
        try {
            $ship_company_id = WuliuShipCompany::ZHONGGU;
            $type = WuliuSeaWaybill::TYPE_CHUKOU;
            $sail_scheduleNameIndex = 3;
            $sail_scheduleVoyageIndex = 4;
            $numberIndex = 5;
            $case_numberIndex = 8;
            $qf_numberIndex = 9;
            $boxIndex = 12;
            $liaison_remarkIndex = 14;
            $liaisonIndex = 18;
            $liaison_mobileIndex = 21;
            $liaison_address_detailIndex = 16;

            // $good_nameIndex = 12;
            // $ship_company_towing_feeIndex = 14;
            // $liaison_address_detailIndex2 = 16;
            // $partner_nameIndex = 0;
            // $car_numberInfo = 8;
            // $liaison_address_detailIndex3 = 10;
            // $car_numberIndex = 13;
            // $weightIndex = 5;
            // $boxIndex = 5;
            // $car_finished_dateIndex = 14;
            // $liaison_address_detailIndex2 = 9;
            // $liaison_address_detailIndex3 = 10;
            // $liaisonIndex = 4;
            // $tosIndex = 6;
            // $box_main_codeIndex = 8;
            // $partner_nameIndex = 9;
            // $liaison_remarkIndex = 10;

            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $sailScheduleMatchingModels = WuliuSailSchedule::get();
            $sailScheduleMatchingModelsArray = $sailScheduleMatchingModels->toArray();
            $carMatchingModels = WuliuCar::get();
            $carMatchingModelsArray = $carMatchingModels->toArray();
            // $partnerMatchingModels = WuliuPartner::get();
            // $partnerMatchingModelsArray = $partnerMatchingModels->toArray();

            $insertSeaWaybillArray = $existsNumberAndCaseNumberArray = [];
            foreach ($dataArray as $key => &$value) {
                if ($key === 0) {
                    continue;
                }
                // 船期
                if (! $value[$sail_scheduleNameIndex] || ! $value[$sail_scheduleVoyageIndex]) {
                    throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '请先在表格中填写每一个船期信息');
                }
                $sail_schedule_id = WuliuSailSchedule::getIdByShipCompanyNameAndNameAndVoyage($sailScheduleMatchingModelsArray, $ship_company_id, $value[$sail_scheduleNameIndex], $value[$sail_scheduleVoyageIndex]);
                // var_dump($sail_schedule_id);
                // return $this->responseJson(ServiceCode::SUCCESS, 1);
                // return $this->responseJson(ServiceCode::SUCCESS, [$sailScheduleMatchingModelsArray, $sail_schedule_id, $ship_company_id, $sail_schedule_array[0], $sail_schedule_array[1]]);
                // var_dump();
                if (! $sail_schedule_id) {
                    $sailScheduleModel = new WuliuSailSchedule();
                    $sailScheduleModel->name = $value[$sail_scheduleNameIndex];
                    $sailScheduleModel->voyage = $value[$sail_scheduleVoyageIndex];
                    $sailScheduleModel->ship_company_id = $ship_company_id;
                    $sailScheduleModel->save();
                    $sailScheduleMatchingModelsArray[] = $sailScheduleModel->toArray();
                    $sail_schedule_id = $sailScheduleModel->id;
                }

                // 运单号+箱号 存在，过滤
                $existsNumberAndCaseNumberStatus = WuliuSeaWaybill::checkIsExistsByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if ($existsNumberAndCaseNumberStatus) {
                    $existsNumberAndCaseNumberArray[] = [
                        'number' => $value[$numberIndex],
                        'case_number' => $value[$case_numberIndex],
                    ];
                    continue;
                }

                $seaWaybillSaveArray = [];
                $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
                $seaWaybillSaveArray['sail_schedule_id'] = $sail_schedule_id;
                $seaWaybillSaveArray['number'] = $value[$numberIndex];
                $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                $seaWaybillSaveArray['box'] = $value[$boxIndex];
                $seaWaybillSaveArray['qf_number'] = $value[$qf_numberIndex];
                // $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex];
                // $seaWaybillSaveArray['ship_company_towing_fee'] = $value[$ship_company_towing_feeIndex];
                $seaWaybillSaveArray['type'] = $type;
                $seaWaybillSaveArray['liaison'] = $value[$liaisonIndex];
                $seaWaybillSaveArray['liaison_mobile'] = $value[$liaison_mobileIndex];
                $seaWaybillSaveArray['liaison_remark'] = $value[$liaison_remarkIndex];
                $seaWaybillSaveArray['liaison_address_detail'] = $value[$liaison_address_detailIndex];
                # 代理
                $seaWaybillSaveArray['created_at'] = date('Y-m-d H:i:s');
                $insertSeaWaybillArray[] = $seaWaybillSaveArray;
                unset($seaWaybillSaveArray);
            }
            // var_dump($insertSeaWaybillArray);
            $numberCount = 0;
            if ($insertSeaWaybillArray) {
                WuliuSeaWaybill::insert($insertSeaWaybillArray);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
                $numberCount = count($insertSeaWaybillArray);
            }
            if ($existsNumberAndCaseNumberArray) {
                $text = '以下运单已存在：';
                foreach ($existsNumberAndCaseNumberArray as $key => $value) {
                    $text .= "[运单号：{$value['number']}-箱号：{$value['case_number']}] ";
                }

                Db::commit();
                return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！<br>{$text}");
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！");
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function importTencentZhonggu2023()
    {
        try {
            $file = $this->request->file('uploadFile');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        // var_dump($dataArray[0]);

        Db::beginTransaction();
        try {
            $ship_company_id = WuliuShipCompany::ZHONGGU;
            $type = WuliuSeaWaybill::TYPE_JINKOU;
            $sail_scheduleIndex = 0;
            $numberIndex = 1;
            $case_numberIndex = 2;
            $good_nameIndex = 3;
            $ship_company_towing_feeIndex = 4;
            $boxIndex = 5;
            $liaison_address_detailIndex = 6;
            $liaisonIndex = 7;
            $liaison_mobileIndex = 8;
            $car_finished_dateIndex = 9;
            $car_numberInfo = 10;
            $receipt_statusIndex = 11;
            $poundbill_statusIndex = 12;
            $liaison_remarkIndex = 13;

            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $sailScheduleMatchingModels = WuliuSailSchedule::with(['shipCompany'])
                ->get();
            $sailScheduleMatchingModelsArray = $sailScheduleMatchingModels->toArray();
            $carMatchingModels = WuliuCar::get();
            $carMatchingModelsArray = $carMatchingModels->toArray();
            $insertSeaWaybillArray = $existsNumberAndCaseNumberArray = [];
            foreach ($dataArray as $key => &$value) {
                if ($key === 0) {
                    continue;
                }
                // 船期
                if (! $value[$sail_scheduleIndex]) {
                    var_dump($value, $sail_scheduleIndex);
                    throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "请先在表格中填写每一个船期 {$value[$sail_scheduleIndex]}");
                }
                $sail_schedule_array = $this->getImportFotmatSailScheduleNameAndVoyavge($value[$sail_scheduleIndex]);
                if (! $sail_schedule_array) {
                    var_dump();
                    throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "船期数据{{$value[$sail_scheduleIndex]}}有误");
                }

                $sail_schedule_id = WuliuSailSchedule::getIdByShipCompanyNameAndNameAndVoyage($sailScheduleMatchingModelsArray, $ship_company_id, $sail_schedule_array[0], $sail_schedule_array[1]);
                // var_dump($sail_schedule_id);
                // return $this->responseJson(ServiceCode::SUCCESS, 1);
                // return $this->responseJson(ServiceCode::SUCCESS, [$sailScheduleMatchingModelsArray, $sail_schedule_id, $ship_company_id, $sail_schedule_array[0], $sail_schedule_array[1]]);
                // var_dump();
                if (! $sail_schedule_id) {
                    $sailScheduleModel = new WuliuSailSchedule();
                    $sailScheduleModel->name = $sail_schedule_array[0];
                    $sailScheduleModel->voyage = $sail_schedule_array[1];
                    $sailScheduleModel->ship_company_id = $ship_company_id;
                    $sailScheduleModel->save();
                    $sailScheduleMatchingModelsArray[] = $sailScheduleModel->toArray();
                    $sail_schedule_id = $sailScheduleModel->id;
                }

                // 运单号+箱号 存在，过滤
                $existsNumberAndCaseNumberStatus = WuliuSeaWaybill::checkIsExistsByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if ($existsNumberAndCaseNumberStatus) {
                    $existsNumberAndCaseNumberArray[] = [
                        'number' => $value[$numberIndex],
                        'case_number' => $value[$case_numberIndex],
                    ];
                    continue;
                }

                # 车辆 + 派送日期
                $a0 = $value[$car_numberInfo];
                if ($value[$car_numberInfo]) {
                    $value[$car_numberInfo] = str_replace("\t", ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('     ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('    ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('   ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('  ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace([
                        '送',
                        '有地',
                        ' 忆',
                        '忆',
                        ' 已',
                        '已',
                        '对',
                        '四',
                        '哥',
                        '已對',
                        '付',
                        '收',
                        '装',
                    ], '', $value[$car_numberInfo]);
                    $car_number = $value[$car_numberInfo];
                    $car_id = WuliuCar::getIdByNumber($carMatchingModelsArray, $car_number);
                    if (! $car_id) {
                        // 自动创建
                        $carModel = new WuliuCar();
                        $carModel->number = $car_number;
                        $carModel->save();
                        $carMatchingModelsArray[] = $carModel->toArray();
                        $value['car_id'] = $carModel->id;

                        // 不自动创建
                        // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "车牌号{{$numberTemp}}不存在，请先创建");
                    }
                    $value['car_id'] = $car_id;
                }
                if (isset($value[$car_finished_dateIndex])) {
                    $car_finished_date = trim($value[$car_finished_dateIndex]);
                    $timeStr = str_replace([
                        '送',
                        "\t",
                        '发货方日鑫鱼粉厂',
                        '有地',
                        ' 忆',
                        '忆',
                        '已',
                        '对',
                        '對',
                        '阿海',
                    ], '', '2023.' . $car_finished_date);
                    $a1 = $timeStr;
                    $timeStr = str_replace('号', '日', $timeStr);
                    $a2 = $timeStr;
                    $timeStr = str_replace([
                        '号',
                        '日',
                        '提',
                    ], '', $timeStr);
                    $a3 = $timeStr;
                    // var_dump($timeStr);
                    $timeStr = str_replace([
                        '年',
                        '月',
                        '日',
                        '号',
                    ], '-', $timeStr);
                    $a4 = $timeStr;
                    $timeStr = str_replace('.', '-', $timeStr);
                    $a5 = $timeStr;

                    if (! $timestamp = strtotime($timeStr)) {
                        return $this->responseJson(ServiceCode::SUCCESS, $a1 . $a2 . $a3 . $a4 . $a5 . '---' . $car_finished_date, $a0);
                    }
                    // var_dump($timeStr);
                    // var_dump($timestamp);
                    $value['car_finished_date'] = date('Y-m-d', $timestamp);
                }

                $seaWaybillSaveArray = [];
                $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
                $seaWaybillSaveArray['sail_schedule_id'] = $sail_schedule_id;
                $seaWaybillSaveArray['number'] = $value[$numberIndex];
                $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex];
                $seaWaybillSaveArray['ship_company_towing_fee'] = $value[$ship_company_towing_feeIndex];
                $seaWaybillSaveArray['box'] = $value[$boxIndex];
                // $seaWaybillSaveArray['qf_number'] = $value[$qf_numberIndex];
                switch ($value[$receipt_statusIndex]) {
                    case '':
                        $seaWaybillSaveArray['receipt_status'] = WuliuSeaWaybill::RECEIPT_STATUS_DEFAULT;
                        break;
                    case '已传':
                        $seaWaybillSaveArray['receipt_status'] = WuliuSeaWaybill::RECEIPT_STATUS_UPLOADED;
                        break;
                    default:
                        return $this->responseJson(ServiceCode::SUCCESS, '签收单状态有误：' . $value[$receipt_statusIndex]);
                        break;
                }
                switch ($value[$poundbill_statusIndex]) {
                    case '':
                        $seaWaybillSaveArray['poundbill_status'] = WuliuSeaWaybill::POUNDBILL_STATUS_DEFAULT;
                        break;
                    case '单未拿回':
                        $seaWaybillSaveArray['poundbill_status'] = WuliuSeaWaybill::POUNDBILL_STATUS_NOT_TAKEN;
                        break;
                    case '已寄':
                        $seaWaybillSaveArray['poundbill_status'] = WuliuSeaWaybill::POUNDBILL_STATUS_POSTED;
                        break;
                    default:
                        return $this->responseJson(ServiceCode::SUCCESS, '磅单状态有误：' . $value[$poundbill_statusIndex]);
                        break;
                }
                $seaWaybillSaveArray['liaison_address_detail'] = $value[$liaison_address_detailIndex];
                $seaWaybillSaveArray['liaison'] = $value[$liaisonIndex];
                $seaWaybillSaveArray['liaison_mobile'] = $value[$liaison_mobileIndex];
                $seaWaybillSaveArray['type'] = $type;
                $seaWaybillSaveArray['liaison_remark'] = $value[$liaison_remarkIndex];
                # 代理
                $seaWaybillSaveArray['created_at'] = date('Y-m-d H:i:s');
                $insertSeaWaybillArray[] = $seaWaybillSaveArray;
                unset($seaWaybillSaveArray);
            }
            // var_dump($insertSeaWaybillArray);
            $numberCount = 0;
            if ($insertSeaWaybillArray) {
                WuliuSeaWaybill::insert($insertSeaWaybillArray);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
                $numberCount = count($insertSeaWaybillArray);
            }
            if ($existsNumberAndCaseNumberArray) {
                $text = '以下运单已存在：';
                foreach ($existsNumberAndCaseNumberArray as $key => $value) {
                    $text .= "[运单号：{$value['number']}-箱号：{$value['case_number']}] ";
                }

                Db::commit();
                return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！<br>{$text}");
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！");
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function importTencentZhonggu2023FixExistsData()
    {
        try {
            $file = $this->request->file('uploadFile');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        // var_dump($dataArray[0]);

        Db::beginTransaction();
        try {
            $ship_company_id = WuliuShipCompany::ZHONGGU;
            $type = WuliuSeaWaybill::TYPE_JINKOU;
            $sail_scheduleIndex = 0;
            $numberIndex = 1;
            $case_numberIndex = 2;
            $good_nameIndex = 3;
            $ship_company_towing_feeIndex = 4;
            $boxIndex = 5;
            $liaison_address_detailIndex = 6;
            $liaisonIndex = 7;
            $liaison_mobileIndex = 8;
            $car_finished_dateIndex = 9;
            $car_numberInfo = 10;
            $receipt_statusIndex = 11;
            $poundbill_statusIndex = 12;
            $liaison_remarkIndex = 13;

            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $sailScheduleMatchingModels = WuliuSailSchedule::with(['shipCompany'])
                ->get();
            $sailScheduleMatchingModelsArray = $sailScheduleMatchingModels->toArray();
            $carMatchingModels = WuliuCar::get();
            $carMatchingModelsArray = $carMatchingModels->toArray();
            $insertSeaWaybillArray = $notExistsSeaWaybillModelArray = [];
            foreach ($dataArray as $key => &$value) {
                if ($key === 0) {
                    continue;
                }
                // 船期
                if (! $value[$sail_scheduleIndex]) {
                    var_dump($value, $sail_scheduleIndex);
                    throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "请先在表格中填写每一个船期 {$value[$sail_scheduleIndex]}");
                }
                $sail_schedule_array = $this->getImportFotmatSailScheduleNameAndVoyavge($value[$sail_scheduleIndex]);
                if (! $sail_schedule_array) {
                    throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "船期数据{{$value[$sail_scheduleIndex]}}有误");
                }

                $sail_schedule_id = WuliuSailSchedule::getIdByShipCompanyNameAndNameAndVoyage($sailScheduleMatchingModelsArray, $ship_company_id, $sail_schedule_array[0], $sail_schedule_array[1]);
                // var_dump($sail_schedule_id);
                // return $this->responseJson(ServiceCode::SUCCESS, 1);
                // return $this->responseJson(ServiceCode::SUCCESS, [$sailScheduleMatchingModelsArray, $sail_schedule_id, $ship_company_id, $sail_schedule_array[0], $sail_schedule_array[1]]);
                // var_dump();
                if (! $sail_schedule_id) {
                    $sailScheduleModel = new WuliuSailSchedule();
                    $sailScheduleModel->name = $sail_schedule_array[0];
                    $sailScheduleModel->voyage = $sail_schedule_array[1];
                    $sailScheduleModel->ship_company_id = $ship_company_id;
                    $sailScheduleModel->save();
                    $sailScheduleMatchingModelsArray[] = $sailScheduleModel->toArray();
                    $sail_schedule_id = $sailScheduleModel->id;
                }

                // 运单号+箱号 存在，过滤
                $existsSeaWaybillModelArray = WuliuSeaWaybill::getByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if (! $existsSeaWaybillModelArray) {
                    // var_dump('不存在的运单,不作处理');
                    $notExistsSeaWaybillModelArray[] = [
                        'number' => $value[$numberIndex],
                        'case_number' => $value[$case_numberIndex],
                    ];
                    continue;
                }
                // var_dump($existsSeaWaybillModelArray);

                # 车辆 + 派送日期
                $a0 = $value[$car_numberInfo];
                if ($value[$car_numberInfo]) {
                    $value[$car_numberInfo] = str_replace("\t", ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('     ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('    ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('   ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('  ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace([
                        '送',
                        '有地',
                        ' 忆',
                        '忆',
                        ' 已',
                        '已',
                        '对',
                        '四',
                        '哥',
                        '已對',
                        '付',
                        '收',
                        '装',
                    ], '', $value[$car_numberInfo]);
                    $car_number = $value[$car_numberInfo];
                    $car_id = WuliuCar::getIdByNumber($carMatchingModelsArray, $car_number);
                    if (! $car_id) {
                        // 自动创建
                        $carModel = new WuliuCar();
                        $carModel->number = $car_number;
                        $carModel->save();
                        $carMatchingModelsArray[] = $carModel->toArray();
                        $value['car_id'] = $carModel->id;

                        // 不自动创建
                        // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "车牌号{{$numberTemp}}不存在，请先创建");
                    }
                    $value['car_id'] = $car_id;
                }
                if (isset($value[$car_finished_dateIndex])) {
                    $car_finished_date = trim($value[$car_finished_dateIndex]);
                    $timeStr = str_replace([
                        '送',
                        "\t",
                        '发货方日鑫鱼粉厂',
                        '有地',
                        ' 忆',
                        '忆',
                        '已',
                        '对',
                        '對',
                        '阿海',
                    ], '', '2023.' . $car_finished_date);
                    $a1 = $timeStr;
                    $timeStr = str_replace('号', '日', $timeStr);
                    $a2 = $timeStr;
                    $timeStr = str_replace([
                        '号',
                        '日',
                        '提',
                    ], '', $timeStr);
                    $a3 = $timeStr;
                    // var_dump($timeStr);
                    $timeStr = str_replace([
                        '年',
                        '月',
                        '日',
                        '号',
                    ], '-', $timeStr);
                    $a4 = $timeStr;
                    $timeStr = str_replace('.', '-', $timeStr);
                    $a5 = $timeStr;

                    if (! $timestamp = strtotime($timeStr)) {
                        return $this->responseJson(ServiceCode::SUCCESS, $a1 . $a2 . $a3 . $a4 . $a5 . '---' . $car_finished_date, $a0);
                    }
                    // var_dump($timeStr);
                    // var_dump($timestamp);
                    $value['car_finished_date'] = date('Y-m-d', $timestamp);
                }

                $seaWaybillSaveArray = [];
                $seaWaybillSaveArray['id'] = $existsSeaWaybillModelArray['id'];
                $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'] ?? null;
                $seaWaybillSaveArray['car_id'] = $value['car_id'] ?? null;
                $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
                $seaWaybillSaveArray['sail_schedule_id'] = $sail_schedule_id;
                $seaWaybillSaveArray['number'] = $value[$numberIndex];
                $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex];
                $seaWaybillSaveArray['ship_company_towing_fee'] = $value[$ship_company_towing_feeIndex];
                $seaWaybillSaveArray['box'] = $value[$boxIndex];
                // $seaWaybillSaveArray['qf_number'] = $value[$qf_numberIndex];
                switch ($value[$receipt_statusIndex]) {
                    case '':
                        $seaWaybillSaveArray['receipt_status'] = WuliuSeaWaybill::RECEIPT_STATUS_DEFAULT;
                        break;
                    case '已传':
                        $seaWaybillSaveArray['receipt_status'] = WuliuSeaWaybill::RECEIPT_STATUS_UPLOADED;
                        break;
                    default:
                        $seaWaybillSaveArray['receipt_status'] = WuliuSeaWaybill::RECEIPT_STATUS_DEFAULT;
                        break;
                }
                switch ($value[$poundbill_statusIndex]) {
                    case '':
                        $seaWaybillSaveArray['poundbill_status'] = WuliuSeaWaybill::POUNDBILL_STATUS_DEFAULT;
                        break;
                    case '单未拿回':
                        $seaWaybillSaveArray['poundbill_status'] = WuliuSeaWaybill::POUNDBILL_STATUS_NOT_TAKEN;
                        break;
                    case '已寄':
                        $seaWaybillSaveArray['poundbill_status'] = WuliuSeaWaybill::POUNDBILL_STATUS_POSTED;
                        break;
                    default:
                        $seaWaybillSaveArray['poundbill_status'] = WuliuSeaWaybill::POUNDBILL_STATUS_DEFAULT;
                        break;
                }
                $seaWaybillSaveArray['liaison_address_detail'] = $value[$liaison_address_detailIndex];
                $seaWaybillSaveArray['liaison'] = $value[$liaisonIndex];
                $seaWaybillSaveArray['liaison_mobile'] = $value[$liaison_mobileIndex];
                $seaWaybillSaveArray['type'] = $type;
                $seaWaybillSaveArray['liaison_remark'] = $value[$liaison_remarkIndex];
                # 代理
                $seaWaybillSaveArray['updated_at'] = date('Y-m-d H:i:s');
                $insertSeaWaybillArray[] = $seaWaybillSaveArray;
                unset($seaWaybillSaveArray);
            }
            // var_dump($insertSeaWaybillArray);
            $numberCount = 0;
            if ($insertSeaWaybillArray) {
                (new WuliuSeaWaybill())->updateBatch($insertSeaWaybillArray);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
                $numberCount = count($insertSeaWaybillArray);
            }
            foreach ($dataArray as $key => &$value) {
                if ($key === 0) {
                    continue;
                }
            }
            if ($notExistsSeaWaybillModelArray) {
                $text = '以下运单不存在：';
                foreach ($notExistsSeaWaybillModelArray as $key => $value) {
                    $text .= "[运单号：{$value['number']}-箱号：{$value['case_number']}] ";
                }

                Db::commit();
                return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！<br>{$text}");
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！");
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function importTencentAntongDaomen2023()
    {
        try {
            $file = $this->request->file('uploadFile');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        // var_dump($dataArray[0]);

        Db::beginTransaction();
        try {
            # 船期
            # 代理
            $ship_company_id = WuliuShipCompany::ANTONG;
            $type = WuliuSeaWaybill::TYPE_JINKOU;
            // $sail_scheduleIndex = 0;
            $numberIndex = 1;
            $case_numberIndex = 2;
            $liaison_address_detailIndex = 3;
            $boxIndex = 4;
            $good_nameIndex = 5;
            $ship_company_towing_feeIndex = 6;
            $car_numberInfo = 9;
            $liaisonIndex = 10;
            $receipt_statusIndex = 11;
            $box_reporting_statusIndex = 12;
            $liaison_remarkIndex = 13;
            // $liaison_mobileIndex = 8;
            // $car_finished_dateIndex = 9;

            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $sailScheduleMatchingModels = WuliuSailSchedule::with(['shipCompany'])
                ->get();
            $sailScheduleMatchingModelsArray = $sailScheduleMatchingModels->toArray();
            $carMatchingModels = WuliuCar::get();
            $carMatchingModelsArray = $carMatchingModels->toArray();
            $insertSeaWaybillArray = $existsNumberAndCaseNumberArray = [];
            $updateData = $insertData = [];
            foreach ($dataArray as $key => &$value) {
                if ($key < 3) {
                    continue;
                }

                # 车辆 + 派送日期
                $a0 = $value[$car_numberInfo];
                if ($value[$car_numberInfo]) {
                    $value[$car_numberInfo] = str_replace("\t", ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('     ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('    ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('   ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace('  ', ' ', $value[$car_numberInfo]);
                    $value[$car_numberInfo] = str_replace([
                        '送',
                        '有地',
                        ' 忆',
                        '忆',
                        ' 已',
                        '已',
                        '对',
                        '四',
                        '哥',
                        '已對',
                        '付',
                        '收',
                        '装',
                    ], '', $value[$car_numberInfo]);
                    $array = explode(' ', $value[$car_numberInfo]);
                    if (count($array) > 2) {
                        // var_dump($value[$car_numberInfo], $value[$liaison_address_detailIndex2]);
                        return $this->responseJson(ServiceCode::SUCCESS, $value[$car_numberInfo]);
                    }
                    $car_number = $array[0];
                    $car_id = WuliuCar::getIdByNumber($carMatchingModelsArray, $car_number);
                    if (! $car_id) {
                        // 自动创建
                        $carModel = new WuliuCar();
                        $carModel->number = $car_number;
                        $carModel->save();
                        $carMatchingModelsArray[] = $carModel->toArray();
                        $value['car_id'] = $carModel->id;

                        // 不自动创建
                        // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "车牌号{{$numberTemp}}不存在，请先创建");
                    }
                    $value['car_id'] = $car_id;

                    if (isset($array[1])) {
                        $car_finished_date = trim($array[1]);
                        $timeStr = str_replace([
                            '送',
                            "\t",
                            '发货方日鑫鱼粉厂',
                            '有地',
                            ' 忆',
                            '忆',
                            '已',
                            '对',
                            '對',
                            '阿海',
                        ], '', '2022.' . $car_finished_date);
                        $a1 = $timeStr;
                        $timeStr = str_replace('号', '日', $timeStr);
                        $a2 = $timeStr;
                        $timeStr = str_replace([
                            '号',
                            '日',
                            '提',
                        ], '', $timeStr);
                        $a3 = $timeStr;
                        // var_dump($timeStr);
                        $timeStr = str_replace([
                            '年',
                            '月',
                            '日',
                            '号',
                        ], '-', $timeStr);
                        $a4 = $timeStr;
                        $timeStr = str_replace('.', '-', $timeStr);
                        $a5 = $timeStr;

                        if (! $timestamp = strtotime($timeStr)) {
                            return $this->responseJson(ServiceCode::SUCCESS, $a1 . $a2 . $a3 . $a4 . $a5 . '---' . $car_finished_date, $a0);
                        }
                        // var_dump($timeStr);
                        // var_dump($timestamp);
                        $value['car_finished_date'] = date('Y-m-d', $timestamp);
                    }
                }

                $seaWaybillSaveArray = [];
                $seaWaybillSaveArray['type'] = $type;
                $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
                // $seaWaybillSaveArray['sail_schedule_id'] = $sail_schedule_id;
                $seaWaybillSaveArray['number'] = $value[$numberIndex];
                $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                $seaWaybillSaveArray['liaison_address_detail'] = $value[$liaison_address_detailIndex];
                $seaWaybillSaveArray['box'] = $value[$boxIndex];
                $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex];
                $seaWaybillSaveArray['ship_company_towing_fee'] = $value[$ship_company_towing_feeIndex];
                $seaWaybillSaveArray['car_id'] = $value['car_id'] ?? null;
                $seaWaybillSaveArray['car_finished_date'] = $value['car_finished_date'] ?? null;
                $seaWaybillSaveArray['liaison'] = $value[$liaisonIndex];
                $seaWaybillSaveArray['liaison_remark'] = $value[$liaison_remarkIndex];
                // $seaWaybillSaveArray['qf_number'] = $value[$qf_numberIndex];
                switch ($value[$receipt_statusIndex]) {
                    case '':
                        $seaWaybillSaveArray['receipt_status'] = WuliuSeaWaybill::RECEIPT_STATUS_DEFAULT;
                        break;
                    case '已传':
                        $seaWaybillSaveArray['receipt_status'] = WuliuSeaWaybill::RECEIPT_STATUS_UPLOADED;
                        break;
                    default:
                        return $this->responseJson(ServiceCode::SUCCESS, '签收单状态有误：' . $value[$receipt_statusIndex]);
                        break;
                }
                switch ($value[$box_reporting_statusIndex]) {
                    case '':
                        $seaWaybillSaveArray['box_reporting_status'] = WuliuSeaWaybill::BOX_REPORTING_STATUS_DEFAULT;
                        break;
                    case '未报备':
                        $seaWaybillSaveArray['box_reporting_status'] = WuliuSeaWaybill::BOX_REPORTING_STATUS_NOT_EXEC;
                        break;
                    case '已报备':
                        $seaWaybillSaveArray['box_reporting_status'] = WuliuSeaWaybill::BOX_REPORTING_STATUS_EXEC;
                        break;
                    default:
                        return $this->responseJson(ServiceCode::SUCCESS, '磅单状态有误：' . $value[$box_reporting_statusIndex]);
                        break;
                }
                // switch ($value[$poundbill_statusIndex]) {
                //     case '':
                //         $seaWaybillSaveArray['poundbill_status'] = WuliuSeaWaybill::POUNDBILL_STATUS_DEFAULT;
                //         break;
                //     case '单未拿回':
                //         $seaWaybillSaveArray['poundbill_status'] = WuliuSeaWaybill::POUNDBILL_STATUS_NOT_TAKEN;
                //         break;
                //     case '已寄':
                //         $seaWaybillSaveArray['poundbill_status'] = WuliuSeaWaybill::POUNDBILL_STATUS_POSTED;
                //         break;
                //     default:
                //         return $this->responseJson(ServiceCode::SUCCESS, '磅单状态有误：' . $value[$poundbill_statusIndex]);
                //         break;
                // }

                # 代理

                // 运单号+箱号 存在，过滤
                $existsModel = WuliuSeaWaybill::getByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if ($existsModel) {
                    $seaWaybillSaveArray['id'] = $existsModel['id'];
                    $seaWaybillSaveArray['updated_at'] = date('Y-m-d H:i:s');
                    $updateData[] = $seaWaybillSaveArray;
                    continue;
                }
                $seaWaybillSaveArray['created_at'] = date('Y-m-d H:i:s');
                $insertData[] = $seaWaybillSaveArray;

                unset($seaWaybillSaveArray);
            }
            // var_dump($insertSeaWaybillArray);
            if ($insertData) {
                WuliuSeaWaybill::insert($insertData);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
                $insertDataCount = count($insertData);
            }
            if ($updateData) {
                (new WuliuSeaWaybill())->updateBatch($updateData);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
                $updateDataCount = count($updateData);
            }

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS, "共更新{$updateDataCount}条，插入{$insertDataCount}数据！");
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function importAntongSystemCYDaoGang()
    {
        try {
            $file = $this->request->file('uploadFile');
            /** Load $inputFileName to a Spreadsheet Object  */
            $spreadsheet = IOFactory::load($file->getRealPath());
        } catch (ReaderExcetpion $e) {
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '文件有误，请联系开发人员');
        }

        // 读取文件
        $dataArray = $spreadsheet->getActiveSheet()
            ->toArray();
        Tools::paramsFilter($dataArray);
        // var_dump($dataArray[0]);

        Db::beginTransaction();
        try {
            # 船期
            # 代理
            $ship_company_id = WuliuShipCompany::ANTONG;
            $type = WuliuSeaWaybill::TYPE_JINKOU;
            $numberIndex = 0;
            $case_numberIndex = 1;
            $partnerIndex = 5;
            $sail_schedule_nameIndex = 8;
            $sail_schedule_voyageIndex = 9;
            $fh_statusIndex = 10;
            $sail_schedule_arrival_dateIndex = 11;
            $partner_print_feeIndex = 13;
            $boxIndex = 18;
            $qf_numberIndex = 19;
            $box_main_codeIndex = 20;
            $good_nameIndex = 21;

            $seaWaybillMatchingModels = WuliuSeaWaybill::get();
            $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
            $sailScheduleMatchingModels = WuliuSailSchedule::with(['shipCompany'])
                ->get();
            $sailScheduleMatchingModelsArray = $sailScheduleMatchingModels->toArray();
            // $carMatchingModels = WuliuCar::get();
            // $carMatchingModelsArray = $carMatchingModels->toArray();
            $partnerMatchingModels = WuliuPartner::get();
            $partnerMatchingModelsArray = $partnerMatchingModels->toArray();
            $updateData = $insertData = [];
            foreach ($dataArray as $key => &$value) {
                if ($key <= 2) {
                    continue;
                }

                $seaWaybillSaveArray = [];
                $seaWaybillSaveArray['type'] = $type;
                $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
                if ($value[$sail_schedule_nameIndex] && $value[$sail_schedule_voyageIndex]) {
                    $sail_schedule_id = WuliuSailSchedule::getIdByShipCompanyNameAndNameAndVoyage($sailScheduleMatchingModelsArray, $ship_company_id, $value[$sail_schedule_nameIndex], $value[$sail_schedule_voyageIndex]);
                    // var_dump($sail_schedule_id);
                    // return $this->responseJson(ServiceCode::SUCCESS, 1);
                    // return $this->responseJson(ServiceCode::SUCCESS, [$sailScheduleMatchingModelsArray, $sail_schedule_id, $ship_company_id, $sail_schedule_array[0], $sail_schedule_array[1]]);
                    // var_dump();
                    if (! $sail_schedule_id) {
                        $sailScheduleModel = new WuliuSailSchedule();
                        $sailScheduleModel->name = $value[$sail_schedule_nameIndex];
                        $sailScheduleModel->voyage = $value[$sail_schedule_voyageIndex];
                        if ($value[$sail_schedule_arrival_dateIndex]) {
                            $sailScheduleModel->arrival_date = date('Y-m-d', strtotime($value[$sail_schedule_arrival_dateIndex]));
                        }
                        $sailScheduleModel->ship_company_id = $ship_company_id;
                        $sailScheduleModel->save();
                        $sailScheduleMatchingModelsArray[] = $sailScheduleModel->toArray();
                        $sail_schedule_id = $sailScheduleModel->id;
                    }
                    $seaWaybillSaveArray['sail_schedule_id'] = $sail_schedule_id;
                } else {
                    $seaWaybillSaveArray['sail_schedule_id'] = null;
                }
                $partner_id = WuliuPartner::getIdByName($partnerMatchingModelsArray, $value[$partnerIndex]);
                if (! $partner_id) {
                    var_dump($value);
                    throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, $value[$partnerIndex] . '不存在');
                    $partnerModel = new WuliuPartner();
                    $partnerModel->name = $value[$partnerIndex];
                    $partnerModel->save();
                    $partnerMatchingModelsArray[] = $partnerModel->toArray();
                    $partner_id = $partnerModel->id;
                }
                $seaWaybillSaveArray['partner_id'] = $partner_id;

                switch ($value[$fh_statusIndex]) {
                    case '未放货':
                        $seaWaybillSaveArray['fh_status'] = WuliuSeaWaybill::FH_STATUS_NO;
                        break;
                    case '已放货':
                        $seaWaybillSaveArray['fh_status'] = WuliuSeaWaybill::FH_STATUS_YES;
                        break;
                    default:
                        $seaWaybillSaveArray['fh_status'] = WuliuSeaWaybill::FH_STATUS_DEFAULT;
                        break;
                }

                $seaWaybillSaveArray['number'] = $value[$numberIndex];
                $seaWaybillSaveArray['case_number'] = $value[$case_numberIndex];
                $seaWaybillSaveArray['box'] = $value[$boxIndex];
                $seaWaybillSaveArray['good_name'] = $value[$good_nameIndex];
                $seaWaybillSaveArray['partner_print_fee'] = $value[$partner_print_feeIndex];
                $seaWaybillSaveArray['qf_number'] = $value[$qf_numberIndex];
                $seaWaybillSaveArray['box_main_code'] = $value[$box_main_codeIndex];
                $seaWaybillSaveArray['tos'] = WuliuSeaWaybill::TOS_DAOGANG;

                // 运单号+箱号 存在，过滤
                $existsModel = WuliuSeaWaybill::getByNumberAndCaseNumber($seaWaybillMatchingModelsArray, $value[$numberIndex], $value[$case_numberIndex]);
                if ($existsModel) {
                    $seaWaybillSaveArray['id'] = $existsModel['id'];
                    $seaWaybillSaveArray['updated_at'] = date('Y-m-d H:i:s');
                    $updateData[] = $seaWaybillSaveArray;
                    continue;
                }
                $seaWaybillSaveArray['created_at'] = $value[$sail_schedule_arrival_dateIndex] ?? date('Y-m-d H:i:s');
                $insertData[] = $seaWaybillSaveArray;

                unset($seaWaybillSaveArray);
            }
            // return $this->responseJson(ServiceCode::SUCCESS, $insertData);
            if ($insertData) {
                WuliuSeaWaybill::insert($insertData);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
            }
            $insertDataCount = count($insertData);
            if ($updateData) {
                (new WuliuSeaWaybill())->updateBatch($updateData);
                // 前提：每个文档的根式都不同，先验证一下SQL字段
            }
            $updateDataCount = count($updateData);

            Db::commit();

            return $this->responseJson(ServiceCode::SUCCESS, "共更新{$updateDataCount}条，插入{$insertDataCount}数据！");
        } catch (Exception $e) {
            Db::rollBack();
            throw $e;
        }
    }

    /**
     * 查出不存在的船公司信息.
     * @param array $sheetArray 查的数据
     * @param array $matchingModelsArray 数组集合
     * @param array $problemArray 不存在的船公司信息 数组
     */
    public function getNotExistsShipCompanyData(array $sheetArray, array $matchingModelsArray, array &$problemArray)
    {
        foreach ($sheetArray as $sheet) {
            $exists = false;
            foreach ($matchingModelsArray as $matchingArray) {
                if ($sheet === $matchingArray['name']) {
                    $exists = true;
                    break;
                }
            }
            if (! $exists) {
                $problemArray[] = $sheet;
            }
        }
    }

    /**
     * 查出不存在的船期信息.
     * @param array $sheetArray 查的数据
     * @param array $matchingModelsArray 数组集合
     * @param array $problemArray 不存在的船期信息 数组
     */
    public function getNotExistsSailScheduleData(array $sheetArray, array $matchingModelsArray, array &$problemArray)
    {
        foreach ($sheetArray as $sheet) {
            $exists = false;
            foreach ($matchingModelsArray as $matchingArray) {
                if ($sheet['ship_company_name'] === $matchingArray['ship_company']['name']
                    && $sheet['sail_schedule_name'] === $matchingArray['name']) {
                    $exists = true;
                    break;
                }
            }
            if (! $exists) {
                $problemArray[] = $sheet;
            }
        }
    }

    /**
     * 查出存在的运单信息.
     * @param array $sheetArray 查的数据
     * @param array $matchingModelsArray 数组集合
     */
    public function getExistsSeaWaybillData(array $sheetArray, array $matchingModelsArray, array &$array)
    {
        foreach ($sheetArray as $sheet) {
            foreach ($matchingModelsArray as $matchingArray) {
                if ($sheet['number'] === $matchingArray['number']
                    && $sheet['case_number'] === $matchingArray['case_number']) {
                    $array[] = $sheet;
                    break;
                }
            }
        }
    }

    /**
     * 获取导入船期字段 格式处理后的数据.
     * @param mixed $str
     */
    public function getImportFotmatSailScheduleNameAndVoyavge($str)
    {
        try {
            //   $str_1 = str_replace([' ', '-', '/'], '|', '吉航87 2247S');
            //   $str_2 = str_replace([' ', '-', '/'], '|', '吉航87-2247S');
            //   $str_3 = str_replace([' ', '-', '/'], '|', '吉航87/2247S');
            //   throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, $str_1 . $str_2 . $str_3);
            $str = trim($str);
            $replace_str = str_replace([
                ' ',
                '  ',
                '-',
                '/',
            ], '|', $str);
            $arr = explode('|', $replace_str);
            if (count($arr) !== 2) {
                return false;
            }
            foreach ($arr as $key => &$value) {
                $value = trim($value);
            }
            return $arr;
        } catch (Exception $e) {
            return false;
        }
    }

    public function downloadImportCommonFile()
    {
    }
}
