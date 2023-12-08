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

namespace App\Service\Business\Wuliu\SeaWaybill;

use App\Constant\ServiceCode;
use App\Model\WuliuCar;
use App\Model\WuliuSailSchedule;
use App\Model\WuliuSeaWaybill;
use App\Model\WuliuShipCompany;
use App\Service\Utils\SpreadsheetService;
use App\Utils\Tools;
use Hyperf\HttpMessage\Exception\HttpException;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SeaWaybillService
{
    public function __construct()
    {
    }

    public function createSeaWaybill($items)
    {
        // 如果传入的不是数组，就将其转换为数组
        if (! is_array($items)) {
            $items = [$items];
        }

        // 创建一个海运单数组
        $seawaybillArrays = [];
        foreach ($items as $key => $value);
    }

    /**
     * 检查是否存在存在相同的运单和箱号.
     * @param mixed $number
     * @param mixed $case_number
     */
    public function checkExistsSingle($number, $case_number)
    {
        return WuliuSeaWaybill::select(['id', 'number', 'case_number', 'deleted_at'])
            ->where('number', $number)
            ->where('case_number', $case_number)
            ->first();
        // $seaWaybillMatchingModels = WuliuSeaWaybill::select(['id', 'number', 'case_nunmber', 'deleted_at'])->get();
        // $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
        // foreach ($seaWaybillMatchingModelsArray as $seaWaybillMatchingModelArray) {
        //     if ($number === $seaWaybillMatchingModelArray['number'] && $case_number === $seaWaybillMatchingModelArray['case_number']) {
        //         return true;
        //     }
        // }
        // return false;
    }

    public function updateZongbiao(array $tableDataArray)
    {
        $numberFieldArray = ['运单号'];
        $case_numberFieldArray = ['箱号'];
        $fh_statusFieldArray = ['放货状态'];
        $receipt_statusFieldArray = ['客户签收单'];
        $ship_company_towing_feeFieldArray = ['拖车费', '请派价'];
        $typeFieldArray = ['进口出口'];
        $weightFieldArray = ['单箱重量'];
        $good_nameFieldArray = ['货名'];
        $boxFieldArray = ['箱型', '箱型尺寸'];
        $qf_numberFieldArray = ['铅封号', '封号', '铅封号(客户)'];
        $liaison_FieldArray = ['联系人', '收货人'];
        $liaison_mobileFieldArray = ['联系人电话', '联系电话'];
        $liaison_address_detailFieldArray = ['联系人详细地址', '详细地址', '收货地址', '联系人/联系电话/收货地址', '联系人/联系电话/发货地址'];
        $remarkFieldArray = ['地址备注'];
        $liaison_remarkFieldArray = ['派车要求'];
        $created_atArray = ['派车时间']; # '要求装货时间', '要求送货时间',
        // $car_numberFieldArray = '车辆 派送日期';
        // $partner_towing_feeFieldArray = '二段拖车费';
        $partner_overdue_feeFieldArray = '船公司：滞箱费/超期费';
        // $partner_stockpiling_feeFieldArray = '码头：堆存费';
        // $partner_print_feeFieldArray = '打单费';
        // $partner_clean_feeFieldArray = '洗柜费';
        // $partner_other_feeFieldArray = '其他费用';
        // $partner_other_fee_descFieldArray = '其他费用说明';
        // $partner_stay_poleFieldArray = '加固杆';
        // $partner_remarksFieldArray = '备注';
        $only_notID = 99999999999;
        $mudigangIndex = $qiyungangIndex = $sail_schedule_nameIndex = $sail_schedule_nameIndex = $sail_schedule_voyageIndex = $sail_schedule_arrival_dateIndex = $sail_scheduleIndex = $numberIndex = $case_numberIndex = $qf_numberIndex = $good_nameIndex = $boxIndex = $weightIndex = $fh_statusIndex = $receipt_statusIndex = $ship_company_towing_feeIndex = $liaison_Index = $liaison_mobileIndex = $liaison_address_detailIndex = $liaison_remarkIndex = $typeIndex = $created_atIndex = $remarkIndex = $only_notID;
        foreach ($tableDataArray[0] as $index => $value) {
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
        $seaWaybillMatchingModels = WuliuSeaWaybill::get();
        $seaWaybillMatchingModelsArray = $seaWaybillMatchingModels->toArray();
        $carMatchingModels = WuliuCar::get();
        $carMatchingModelsArray = $carMatchingModels->toArray();
        $sailScheduleMatchingModels = WuliuSailSchedule::get();
        $sailScheduleMatchingModelsArray = $sailScheduleMatchingModels->toArray();
        $insertData = $updateData = [];
        // return $this->responseJson(ServiceCode::SUCCESS, $tableDataArray);

        foreach ($tableDataArray as $key => &$value) {
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
                    throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '船期航次数据有误:' . $value[$sail_scheduleIndex]);
                }
                $sail_schedule_name = $sail_schedule_array[0];
                $sail_schedule_voyage = $sail_schedule_array[1];
            } else {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '无法匹配出船期数据');
            }
            if ($common_ship_company_id) {
                $ship_company_id = $common_ship_company_id;
            } else {
                $ship_company_id = WuliuShipCompany::getIdBySeaWaybillNumber($value[$numberIndex]);
            }
            if (! $ship_company_id) {
                throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '运单号：' . $value[$numberIndex] . '匹配不到船公司');
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
                        throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '无法判断运单 进出口类型' . implode('_', $value));
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
                        throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '无法判断运单 进出口类型:' . $seaWaybillSaveArray['type']);
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
                    throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, '签收单状态有误：' . $value[$receipt_statusIndex]);
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

        return ['insertCount' => $insertCount, 'updateCount' => $updateCount];
    }

    public function getReceipt($models)
    {
        $filename = '签收单';
        $spreadsheet = SpreadsheetService::genExcelByTianchang();
        $worksheet = $spreadsheet->getActiveSheet();

        $spreadsheet->getProperties()
            ->setCreator('Liguoxin')
            ->setLastModifiedBy('Liguoxin')
            ->setTitle($filename);

        $row = $contextStartRow = 1;
        $worksheet->getColumnDimension('A')->setWidth(23);
        $worksheet->getColumnDimension('B')->setWidth(33);
        $worksheet->getColumnDimension('C')->setWidth(20);
        $worksheet->getColumnDimension('D')->setWidth(35);
        foreach ($models as $key => $model) {
            $contextStartRow = $row;
            $worksheet->mergeCells('A' . $row . ':D' . $row);
            $worksheet->getRowDimension($row)->setRowHeight(50);
            $worksheet->getCell('A' . $row)->getStyle()->getFont()->setSize(18)->setBold(false);
            $worksheet->getStyle('A' . $row)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $worksheet->getCell('A' . $row)->setValue('  上海中谷新良物流有限公司送货签收单');

            // 创建一个 Drawing 对象，并设置图片路径和位置
            $drawing = new Drawing();
            $drawing->setName('中谷Logo');
            // $drawing->setDescription('This is an image');
            $drawing->setPath(BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'wuliu' . DIRECTORY_SEPARATOR . 'zhonggulogo.png');
            $drawing->setCoordinates('C' . $row);
            $drawing->setHeight(50);
            $drawing->setWidth(150);
            $drawing->setOffsetX(0);
            $drawing->setOffsetY(0);
            $drawing->setWorksheet($worksheet);
            $worksheet->getStyle('C' . $row)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_CENTER);

            $drawing = new Drawing();
            $drawing->setName('中谷Logo');
            // $drawing->setDescription('This is an image');
            $drawing->setPath(BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'wuliu' . DIRECTORY_SEPARATOR . 'zhongguerweima.png');
            $drawing->setCoordinates('D' . $row);
            $drawing->setHeight(50);
            $drawing->setWidth(150);
            $drawing->setOffsetX(0);
            $drawing->setOffsetY(0);
            $drawing->setWorksheet($worksheet);
            $worksheet->getStyle('D' . $row)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT)
                ->setVertical(Alignment::VERTICAL_CENTER);

            ++$row;
            $worksheet->getRowDimension($row)->setRowHeight(32);
            $worksheet->getCell('A' . $row)->setValue('船名/航次');
            $chuanminghangci = ($model->sailSchedule->name ?? '') . '/' . ($model->sailSchedule->voyage ?? '');
            $worksheet->getCell('B' . $row)->setValue($chuanminghangci);

            $worksheet->getCell('C' . $row)->setValue('运单号');

            $number = $model->number ?? '';
            $worksheet->getCell('D' . $row)->setValue($number);

            ++$row;
            $worksheet->getRowDimension($row)->setRowHeight(32);
            $worksheet->getCell('A' . $row)->setValue('箱/封号');
            $xiangfenghao = ($model->case_number ?? '') . '/' . ($model->qf_number ?? '');
            $worksheet->setCellValue('B' . $row, $xiangfenghao);

            $worksheet->mergeCells('B' . $row . ':C' . $row);
            $xiangfenghao = '';
            $worksheet->getCell('C' . $row)->setValue($xiangfenghao);

            ++$row;
            $worksheet->getRowDimension($row)->setRowHeight(32);
            $worksheet->getCell('A' . $row)->setValue('货物名称');

            $worksheet->getCell('B' . $row)->setValue($model->good_name);
            $worksheet->getCell('C' . $row)->setValue('箱型');
            $worksheet->getCell('D' . $row)->setValue($model->box);

            ++$row;
            $worksheet->getRowDimension($row)->setRowHeight(64);
            $worksheet->getCell('A' . $row)->setValue('收货人/联系电话');

            $worksheet->getCell('B' . $row)->getStyle()->getFont()->setSize(12)->setBold(false);
            $details = ($model->liaison ?? '') . '/' . ($model->liaison_mobile ?? '') . '/' . ($model->details ?? '');
            $details = Tools::phoneEncrypt($details);
            $worksheet->getCell('B' . $row)->setValue($details);
            $worksheet->mergeCells('B' . $row . ':D' . $row);

            ++$row;
            $worksheet->getRowDimension($row)->setRowHeight(64);
            $worksheet->getCell('A' . $row)->setValue('派车要求');
            $worksheet->getCell('B' . $row)->getStyle()->getFont()->setSize(12)->setBold(false);
            $yaoqiu = $model->liaison_remark ?? '';
            $worksheet->getCell('B' . $row)->setValue($yaoqiu);
            $worksheet->mergeCells('B' . $row . ':D' . $row);

            ++$row;
            $worksheet->getRowDimension($row)->setRowHeight(32);
            $worksheet->getCell('A' . $row)->setValue('货柜到达时间');
            $worksheet->getCell('B' . $row)->setValue('');
            $worksheet->getCell('C' . $row)->setValue('货柜卸空时间');
            $worksheet->getCell('D' . $row)->setValue('');

            ++$row;
            $worksheet->getRowDimension($row)->setRowHeight(32);
            $worksheet->getCell('A' . $row)->setValue('车队名称');
            $worksheet->getCell('C' . $row)->setValue('司机姓名/车牌');

            ++$row;
            $worksheet->getRowDimension($row)->setRowHeight(32);
            $worksheet->getCell('A' . $row)->setValue('收货人');
            $worksheet->getCell('B' . $row)->getStyle()->getFont()->setSize(12)->setBold(false);
            $worksheet->getCell('B' . $row)->setValue('');
            $worksheet->mergeCells('B' . $row . ':D' . $row);

            ++$row;
            $worksheet->getRowDimension($row)->setRowHeight(32);
            $worksheet->getCell('A' . $row)->setValue('签字/签章');
            $worksheet->mergeCells('B' . $row . ':C' . $row);
            $year = '         年        月       日';
            $worksheet->getCell('D' . $row)->setValue($year);

            ++$row;
            $worksheet->getRowDimension($row)->setRowHeight(32);
            $worksheet->getCell('A' . $row)->getStyle()->getFont()->setSize(10)->setBold(false);
            $worksheet->mergeCells('A' . $row . ':D' . $row);
            $worksheet->getCell('A' . $row)->setValue('备注：1、如未注明箱体、铅封状况的，视同箱体、铅封完好，箱号、铅封号无误。');

            ++$row;
            $worksheet->getRowDimension($row)->setRowHeight(32);
            $worksheet->getCell('A' . $row)->getStyle()->getFont()->setSize(10)->setBold(false);
            $worksheet->mergeCells('A' . $row . ':D' . $row);
            $worksheet->getCell('A' . $row)->setValue('2、签收说明：个人签收时，请填写完整姓名及身份证号码，并填写日期。公司签收时，请加盖公司的仓库章或者公章，并填写日期。');

            ++$row;
            $worksheet->getRowDimension($row)->setRowHeight(32);
            $worksheet->getCell('A' . $row)->getStyle()->getFont()->setSize(10)->setBold(false);
            $worksheet->mergeCells('A' . $row . ':D' . $row);
            $worksheet->getCell('A' . $row)->setValue('3、请协议车队驾驶员督促收货人填写完整上述签收信息，如因上述信息未填写完整导致的一切责任由协议车队承担。');

            $worksheet->getStyle('A' . ($contextStartRow + 1) . ':D' . $row)
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_CENTER)
                ->setVertical(Alignment::VERTICAL_CENTER);
            $worksheet->getStyle('A' . $contextStartRow . ':D' . $row)
                ->getBorders()
                ->getAllBorders()
                ->setBorderStyle(Border::BORDER_THIN)
                ->setColor(new Color());
            $worksheet->getStyle('A2:D' . $row)->applyFromArray([
                'font' => [
                    // 'size' => 12,
                    'name' => '微软雅黑',
                    // 'bold' => true,
                ],
            ]);

            if (($key + 1) % 2 != 0) {
                ++$row;
                ++$row;
                ++$row;
                ++$row;
            } else {
                // $key是二的倍数，能被二整除，执行xx
                if ($models->count() != $key) {
                    # 不是最后一条数据，继续使用分页符
                    $worksheet->setBreak('A' . $row, Worksheet::BREAK_ROW);
                    ++$row;
                }
            }
            $worksheet->getPageSetup()->addPrintArea('A' . $contextStartRow . ':D' . $row);
        }

        # 设置缩放比例 80%
        $worksheet->getPageSetup()->setScale(75);

        # 设置页边距，类似居中的意思，保证setPrintArea每次打印的页面都是固定格式
        $worksheet->getPageMargins()->setTop(0.2);
        $worksheet->getPageMargins()->setTop(0.2);
        $worksheet->getPageMargins()->setLeft(0.2);
        $worksheet->getPageMargins()->setRight(0.2);

        # 每一页都保持固定格式. 设置为false，这样打印的每一页都会按照你设置的打印区域进行打印，而不会根据内容自动调整。

        $pageSetup = $worksheet->getPageSetup();
        # 设置查看比例是75%
        $worksheet->getSheetView()->setZoomScale(75);
        $pageSetup->setFitToPage(true);
        $pageSetup->setFitToWidth(1);
        $pageSetup->setFitToHeight(0);
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4);
        $pageSetup->setPrintArea('A1:D' . ($row - 1));

        return SpreadsheetService::exportExcelByTianchang($spreadsheet, $filename);
    }
}
