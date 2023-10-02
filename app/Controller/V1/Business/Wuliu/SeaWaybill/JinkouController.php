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
use App\Model\WuliuSailSchedule;
use App\Model\WuliuSeaWaybill;
use App\Model\WuliuShipCompany;
use App\Utils\Tools;
use Hyperf\HttpMessage\Exception\HttpException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\Exception as ReaderExcetpion;

class JinkouController extends SeaWaybillController
{
    // 导入海运单
    public function importZhongGuJinkou()
    {
        $params = $this->getRequestAllFilter();
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
        /**
         * array(33) {
         * [0]=>
         * NULL
         * [1]=>
         * string(6) "序号"
         * [2]=>
         * string(6) "状态"
         * [3]=>
         * string(9) "运单号"
         * [4]=>
         * string(6) "船名"
         * [5]=>
         * string(6) "航次"
         * [6]=>
         * string(12) "特殊派车"
         * [7]=>
         * string(9) "业务号"
         * [8]=>
         * string(18) "要求送货时间"
         * [9]=>
         * string(12) "卸船时间"
         * [10]=>
         * string(12) "放货状态"
         * [11]=>
         * string(12) "是否加急"
         * [12]=>
         * string(15) "客户签收单"
         * [13]=>
         * string(6) "运价"
         * [14]=>
         * string(9) "拖车费"
         * [15]=>
         * string(9) "代垫费"
         * [16]=>
         * string(15) "代垫费备注"
         * [17]=>
         * string(12) "进口出口"
         * [18]=>
         * string(6) "箱号"
         * [19]=>
         * string(6) "封号"
         * [20]=>
         * string(6) "货名"
         * [21]=>
         * string(12) "单箱重量"
         * [22]=>
         * string(6) "箱型"
         * [23]=>
         * string(12) "派车要求"
         * [24]=>
         * string(12) "详细地址"
         * [25]=>
         * string(12) "地址备注"
         * [26]=>
         * string(9) "装货地"
         * [27]=>
         * string(9) "卸货地"
         * [28]=>
         * string(9) "联系人"
         * [29]=>
         * string(12) "联系电话"
         * [30]=>
         * string(12) "司机姓名"
         * [31]=>
         * string(12) "司机电话"
         * [32]=>
         * string(9) "车牌号".
         */
        $ship_company_id = WuliuShipCompany::ZHONGGU;
        $sailScheduleMatchingData = WuliuSailSchedule::where('ship_company_id', $ship_company_id)->get()->toArray();
        // $sailScheduleMatchingData = [];
        // foreach ($sailScheduleModels as $key => $value) {
        //     $sailScheduleMatchingData[$value['']] = $value[''];
        // }

        // 固定文件格式
        $saveArray = [];
        foreach ($dataArray as $key => $row) {
            if ($key === 0) {
                // title
                continue;
            }
            $sail_schedule_id = false;
            foreach ($sailScheduleMatchingData as $sailSchedule) {
                if ($sailSchedule['name'] === $row[4] && $sailSchedule['voyage'] === $row[5]) {
                    $sail_schedule_id = true;
                    break;
                }
            }
            if (! $sail_schedule_id) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "请先在创建船期 {$row[4]}/{$row[5]} 船公司：中谷");
            }
            // 中谷 对账单 - 增值费 - 打单费
            # 船期[船名+航次]能匹配上 并且 该船期是船公司的

            // 是否加急、优先派送

            $seaWaybillSaveArray = [];
            $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
            $seaWaybillSaveArray['sail_schedule_id'] = $sail_schedule_id;
            $seaWaybillSaveArray['number'] = $row[3];
            $seaWaybillSaveArray['case_number'] = $row[18];
            $seaWaybillSaveArray['qf_number'] = $row[19];
            $seaWaybillSaveArray['box'] = $row[22];
            $seaWaybillSaveArray['good_name'] = $row[20];
            $seaWaybillSaveArray['weight'] = $row[21];
            $seaWaybillSaveArray['ship_company_towing_fee'] = $row[14];
            $seaWaybillSaveArray['car_other_fee'] = 0;
            $seaWaybillSaveArray['car_other_fee_desc'] = '';
            $seaWaybillSaveArray['receipt_status'] = $row[9] === '不需要' ? WuliuSeaWaybill::RECEIPT_STATUS_DEFAULT : WuliuSeaWaybill::RECEIPT_STATUS_NOT_UPLOAD;
            $seaWaybillSaveArray['poundbill_status'] = WuliuSeaWaybill::POUNDBILL_STATUS_DEFAULT;
            $seaWaybillSaveArray['liaison'] = $row[28];
            $seaWaybillSaveArray['liaison_mobile'] = $row[29];
            $seaWaybillSaveArray['liaison_address'] = $row[27];
            $seaWaybillSaveArray['liaison_address_detail'] = $row[24];
            $seaWaybillSaveArray['estimated_time'] = $row[8];
            // $seaWaybillModel->car_id = $row[];
            // $seaWaybillModel->driver_name = $row[];
            // $seaWaybillModel->driver_mobile_number = $row[];
            // $seaWaybillModel->driver_id_card = $row[];
            $seaWaybillSaveArray['liaison_remark'] = '';
            $seaWaybillSaveArray['fh_status'] = $row[7] === '已放货' ? WuliuSeaWaybill::FH_STATUS_YES : WuliuSeaWaybill::FH_STATUS_NO;
            $seaWaybillSaveArray['rush_status'] = WuliuSeaWaybill::RUSH_STATUS_NO;
            $seaWaybillSaveArray['created_at'] = date('Y-m-d H:i:s');
            $seaWaybillSaveArray['type'] = WuliuSeaWaybill::TYPE_JINKOU;
            $seaWaybillSaveArray['status'] = WuliuSeaWaybill::STATUS_DEFAULT;

            $saveArray[] = $seaWaybillSaveArray;
        }
        // var_dump($saveArray);
        throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR);
        // 如果存在相同运单号和箱号的数据，则不允许导入
        $seaWaybillModel = new WuliuSeaWaybill();

        $where = [];
        foreach ($saveArray as $key => $value) {
            $where = [
                [
                    'number',
                    '=',
                    $value['number'],
                ],
                [
                    'case_number',
                    '=',
                    $value['case_number'],
                ],
            ];
            if ($key === 0) {
                $seaWaybillModel = $seaWaybillModel->where($where);
                continue;
            }
            $seaWaybillModel = $seaWaybillModel->orWhere($where);
        }

        $existsModels = $seaWaybillModel->get();
        if ($existsModels->isNotEmpty()) {
            $text = '';
            foreach ($existsModels as $key => $value) {
                $text .= '运单号：' . $value->number . ' 箱号：' . $value->case_number . ' ';
            }

            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '已存在' . count($existsModels) . '条数据：' . $text);
        }
        WuliuSeaWaybill::insert($saveArray);
        // 前提：每个文档的根式都不同，先验证一下SQL字段
        $numberCount = count($saveArray);
        return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！");
    }

    // 导入安通海运单
    public function importAntongJinkou()
    {
        $params = $this->getRequestAllFilter();
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

        $ship_company_id = WuliuShipCompany::ANTONG;
        $sailScheduleMatchingData = WuliuSailSchedule::where('ship_company_id', $ship_company_id)->get()->toArray();
        // $sailScheduleMatchingData = [];
        // foreach ($sailScheduleModels as $key => $value) {
        //     $sailScheduleMatchingData[$value['']] = $value[''];
        // }

        // 固定文件格式

        // 船期[船名+航次]能匹配上 并且 该船期是船公司的
        $saveArray = [];
        $importSailScheduleArray = [];
        foreach ($dataArray as $key => $row) {
            if ($key === 0) {
                // title
                continue;
            }

            // 找出所有船名和航次
            $sailScheduleInfo = explode(' ', $row[0]);
            // var_dump($row);
            $name = trim($sailScheduleInfo[0]);
            $voyage = trim($sailScheduleInfo[1]);

            $exists = false;
            foreach ($importSailScheduleArray as $key => $value) {
                if ($value['name'] === $name && $value['voyage'] === $voyage) {
                    $exists = true;
                    break;
                }
            }
            if (! $exists) {
                $importSailScheduleArray[] = [
                    'name' => $name,
                    'voyage' => $voyage,
                ];
            }
        }

        // ----------------------------- 判断是否有 不存在的船期 start -----------------------------
        // 不存在的船期信息 数组
        $notExistsSailSchedulArray = [];

        foreach ($importSailScheduleArray as $importSailSchedule) {
            $existsImportSailSchedule = false;

            // 船期数据(船公司id查询)
            foreach ($sailScheduleMatchingData as $sailSchedule) {
                // 需要导入的船期信息 $importSailScheduleArray
                if ($importSailSchedule['name'] === $sailSchedule['name'] && $importSailSchedule['voyage'] === $sailSchedule['voyage']) {
                    $existsImportSailSchedule = true;
                    break;
                }
            }
            if (! $existsImportSailSchedule) {
                $notExistsSailSchedulArray[] = $importSailSchedule;
            }
        }

        // 有问题，船期不存在
        if ($notExistsSailSchedulArray) {
            $text = '';
            foreach ($notExistsSailSchedulArray as $key => $value) {
                $text .= $value['name'] . '/' . $value['voyage'] . '  |  ';
                # code...
            }
            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, "请先在创建船期 {$text} 船公司：安通");
        }
        // ----------------------------- 判断是否有 不存在的船期 end -----------------------------

        // var_dump($sailScheduleMatchingData);

        // 查询所有不存在的船期，并且创建
        foreach ($dataArray as $key => $row) {
            if ($key === 0) {
                // title
                continue;
            }

            $sail_schedule_id = false;
            foreach ($sailScheduleMatchingData as $key => $value) {
                $sailScheduleInfo = explode(' ', $row[0]);
                $name = trim($sailScheduleInfo[0]);
                $voyage = trim($sailScheduleInfo[1]);
                if ($value['name'] === $name && $value['voyage'] === $voyage) {
                    $sail_schedule_id = $value['id'];
                    break;
                }
            }
            if (! $sail_schedule_id) {
                // var_dump('$sail_schedule_id error:', $row, $sailScheduleInfo);
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '导入错误，请联系管理员');
            }
            /*
             * array(28) {
             * [0]=>
             * string(12) "船名航次"
             * [1]=>
             * string(9) "起运港"
             * [2]=>
             * string(12) "卸船日期"
             * [3]=>
             * string(18) "到期截至日期"
             * [4]=>
             * string(9) "提单号"
             * [5]=>
             * string(6) "箱号"
             * [6]=>
             * string(12) "放货日期"
             * [7]=>
             * string(6) "箱型"
             * [8]=>
             * string(15) "是否有照片"
             * [9]=>
             * string(6) "箱号"
             * [10]=>
             * string(12) "出场日期"
             * [11]=>
             * string(6) "车队"
             * [12]=>
             * string(9) "收货人"
             * [13]=>
             * string(12) "收货地址"
             * [14]=>
             * string(12) "运输条款"
             * [15]=>
             * string(6) "货名"
             * [16]=>
             * string(6) "代理"
             * [17]=>
             * string(9) "压年柜"
             * [18]=>
             * string(12) "超期原因"
             * [19]=>
             * string(12) "用箱天数"
             * [20]=>
             * string(6) "箱主"
             * [21]=>
             * string(6) "备注"
             * [22]=>
             * string(15) "箱内发货单"
             * [23]=>
             * string(9) "进口THC"
             * [24]=>
             * string(18) "目的港换单费"
             * [25]=>
             * string(11) "CY附加费"
             * [26]=>
             * string(15) "压年起开始"
             * [27]=>
             * string(12) "压年截止"
             * }
             */
            $seaWaybillSaveArray = [];
            $seaWaybillSaveArray['ship_company_id'] = $ship_company_id;
            $seaWaybillSaveArray['sail_schedule_id'] = $sail_schedule_id;
            $seaWaybillSaveArray['number'] = $row[4];
            $seaWaybillSaveArray['case_number'] = $row[5];
            $seaWaybillSaveArray['qf_number'] = '';
            $seaWaybillSaveArray['box'] = $row[7];
            $seaWaybillSaveArray['good_name'] = $row[15];
            $seaWaybillSaveArray['weight'] = '';
            $seaWaybillSaveArray['ship_company_towing_fee'] = 0;
            $seaWaybillSaveArray['car_other_fee'] = 0;
            $seaWaybillSaveArray['car_other_fee_desc'] = '';
            $seaWaybillSaveArray['receipt_status'] = WuliuSeaWaybill::RECEIPT_STATUS_DEFAULT;
            $seaWaybillSaveArray['poundbill_status'] = WuliuSeaWaybill::POUNDBILL_STATUS_NOT_TAKEN;
            $seaWaybillSaveArray['liaison'] = $row[12];
            $seaWaybillSaveArray['liaison_mobile'] = '';
            $seaWaybillSaveArray['liaison_address'] = '';
            $seaWaybillSaveArray['liaison_address_detail'] = $row[13];
            // $seaWaybillSaveArray['estimated_time'] = ;
            // $seaWaybillModel->car_id = $row[];
            // $seaWaybillModel->driver_name = $row[];
            // $seaWaybillModel->driver_mobile_number = $row[];
            // $seaWaybillModel->driver_id_card = $row[];
            $seaWaybillSaveArray['liaison_remark'] = '';
            // $seaWaybillSaveArray['fh_status'] = $row[7] === '已放货' ? WuliuSeaWaybill::FH_STATUS_YES : WuliuSeaWaybill::FH_STATUS_NO;
            $seaWaybillSaveArray['fh_status'] = WuliuSeaWaybill::FH_STATUS_NO;
            $seaWaybillSaveArray['rush_status'] = WuliuSeaWaybill::RUSH_STATUS_NO;
            $seaWaybillSaveArray['created_at'] = date('Y-m-d H:i:s');
            $seaWaybillSaveArray['type'] = WuliuSeaWaybill::TYPE_JINKOU;
            $seaWaybillSaveArray['status'] = WuliuSeaWaybill::STATUS_DEFAULT;

            $saveArray[] = $seaWaybillSaveArray;
        }

        // 如果存在相同运单号和箱号的数据，则不允许导入
        $seaWaybillModel = new WuliuSeaWaybill();

        $where = [];
        foreach ($saveArray as $key => $value) {
            $where = [
                [
                    'number',
                    '=',
                    $value['number'],
                ],
                [
                    'case_number',
                    '=',
                    $value['case_number'],
                ],
            ];
            if ($key === 0) {
                $seaWaybillModel = $seaWaybillModel->where($where);
                continue;
            }
            $seaWaybillModel = $seaWaybillModel->orWhere($where);
        }

        $existsModels = $seaWaybillModel->get();
        if ($existsModels->isNotEmpty()) {
            $text = '';
            foreach ($existsModels as $key => $value) {
                $text .= '运单号：' . $value->number . ' 箱号：' . $value->case_number . ' ';
            }

            throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '已存在' . count($existsModels) . '条数据：' . $text);
        }
        WuliuSeaWaybill::insert($saveArray);
        // 前提：每个文档的根式都不同，先验证一下SQL字段
        $numberCount = count($saveArray);
        return $this->responseJson(ServiceCode::SUCCESS, "操作成功，共导入{$numberCount}条数据！");
    }

    // public function importAntongGetFieldByRow($row)
    // {
    //     return [
    //         '',
    //     ];
    // }
}
