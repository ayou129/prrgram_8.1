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

namespace App\Service\Business\Wuliu\Bill;

use App\Model\WuliuSeaWaybill;
use App\Service\Utils\SpreadsheetService;
use App\Service\Utils\WuliuSpreadsheetService;
use App\Utils\Tools;

class PartnerBillStragegy implements BillExportStrategyInterface
{
    public function export($model)
    {
        // 3.合作公司：拖车费
        $seaWaybillModels = WuliuSeaWaybill::where('partner_bill_id', $model->id)
            ->orderBy('car_finished_date', 'asc')
            ->with(['car'])
            ->get();
        $seaWaybillModelsArray = $seaWaybillModels->toArray();

        $appName = env('APP_NAME', '');
        $filename = $appName . $model->title . '对账单';
        $spreadsheet = SpreadsheetService::genExcelByTianchang();
        $spreadsheet->getProperties()
            ->setCreator('Liguoxin')
            ->setLastModifiedBy('Liguoxin')
            ->setTitle($filename);
        // $wuliuSpreadsheetService = new WuliuSpreadsheetService();
        // $wuliuSpreadsheetService->setFilename($filename);
        // $wuliuSpreadsheetService->setPropertCreator('liguoxin');
        // $wuliuSpreadsheetService->setContextFirstRow($contextFirstRow);
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑');
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->getColumnDimension('A')->setWidth(10);
        $worksheet->getColumnDimension('B')->setAutoSize(true);
        $worksheet->getColumnDimension('C')->setAutoSize(true);
        $worksheet->getColumnDimension('D')->setAutoSize(true);
        $worksheet->getColumnDimension('E')->setAutoSize(true);
        $worksheet->getColumnDimension('F')->setWidth(14);
        $worksheet->getColumnDimension('G')->setWidth(10);
        $worksheet->getColumnDimension('H')->setAutoSize(true);
        $worksheet->getColumnDimension('I')->setAutoSize(true);
        $worksheet->getColumnDimension('J')->setAutoSize(true);
        $worksheet->getColumnDimension('K')->setAutoSize(true);
        $worksheet->getColumnDimension('L')->setWidth(20);
        $worksheet->getColumnDimension('M')->setWidth(14);
        $worksheet->getColumnDimension('N')->setWidth(20);
        $worksheet->getColumnDimension('O')->setWidth(10);
        $worksheet->getColumnDimension('P')->setWidth(10);
        $worksheet->getColumnDimension('Q')->setWidth(10);
        $worksheet->getColumnDimension('R')->setWidth(15);
        $worksheet->getColumnDimension('S')->setWidth(10);
        // $worksheet->getColumnDimension('T')->setWidth(15);
        $contextRow = 1;
        $worksheet->setCellValue('A' . $contextRow, '序号');
        $worksheet->setCellValue('B' . $contextRow, '运单号');
        $worksheet->setCellValue('C' . $contextRow, '箱号');
        $worksheet->setCellValue('D' . $contextRow, '铅封号');
        $worksheet->setCellValue('E' . $contextRow, '货名');
        $worksheet->setCellValue('F' . $contextRow, '其他(货重)');
        $worksheet->setCellValue('G' . $contextRow, '联系人');
        $worksheet->setCellValue('H' . $contextRow, '地址');
        $worksheet->setCellValue('I' . $contextRow, '派车日期');
        $worksheet->setCellValue('J' . $contextRow, '派车号');
        $worksheet->setCellValue('K' . $contextRow, '派车费');
        $worksheet->setCellValue('L' . $contextRow, '船公司：滞箱费/超期费');
        $worksheet->setCellValue('M' . $contextRow, '码头：堆存费');
        $worksheet->setCellValue('N' . $contextRow, '换单费');
        $worksheet->setCellValue('O' . $contextRow, '码头：装卸作业费(THC)');
        $worksheet->setCellValue('P' . $contextRow, '打单费');
        $worksheet->setCellValue('Q' . $contextRow, '洗柜费');
        $worksheet->setCellValue('R' . $contextRow, '其他费用');
        $worksheet->setCellValue('S' . $contextRow, '其他费用说明');
        $worksheet->setCellValue('T' . $contextRow, '加固杆');
        // $worksheet->setCellValue('T' . $contextRow, '车牌');

        // context
        ++$contextRow;
        $dataIndex = 1;
        $totalFee = $totalpartner_towing_fee = $totalpartner_overdue_fee = $totalpartner_stockpiling_fee = $totalpartner_huandan_fee = $totalpartner_thc_fee = $totalpartner_print_fee = $totalpartner_clean_fee = $totalpartner_other_fee = $totalpartner_stay_pole = 0;
        foreach ($seaWaybillModelsArray as $seaWaybillModelArray) {
            // 是否第二车
            $worksheet->setCellValue('A' . $contextRow, $dataIndex);
            $worksheet->setCellValue('B' . $contextRow, $seaWaybillModelArray['number']);
            $worksheet->setCellValue('C' . $contextRow, $seaWaybillModelArray['case_number']);
            $worksheet->setCellValue('D' . $contextRow, $seaWaybillModelArray['qf_number']);
            $worksheet->setCellValue('E' . $contextRow, $seaWaybillModelArray['good_name']);
            $worksheet->setCellValue('F' . $contextRow, $seaWaybillModelArray['weight']);
            $worksheet->setCellValue('G' . $contextRow, $seaWaybillModelArray['liaison']);
            $worksheet->setCellValue('H' . $contextRow, $seaWaybillModelArray['liaison_address_detail']);
            $worksheet->setCellValue('I' . $contextRow, $seaWaybillModelArray['car_finished_date']);
            $worksheet->setCellValue('J' . $contextRow, $seaWaybillModelArray['car']['number'] ?? '');
            $worksheet->setCellValue('K' . $contextRow, $seaWaybillModelArray['partner_towing_fee']);
            $worksheet->setCellValue('L' . $contextRow, $seaWaybillModelArray['partner_overdue_fee']);
            $worksheet->setCellValue('M' . $contextRow, $seaWaybillModelArray['partner_stockpiling_fee']);
            $worksheet->setCellValue('N' . $contextRow, $seaWaybillModelArray['partner_huandan_fee']);
            $worksheet->setCellValue('O' . $contextRow, $seaWaybillModelArray['partner_thc_fee']);
            $worksheet->setCellValue('P' . $contextRow, $seaWaybillModelArray['partner_print_fee']);
            $worksheet->setCellValue('Q' . $contextRow, $seaWaybillModelArray['partner_clean_fee']);
            $worksheet->setCellValue('R' . $contextRow, $seaWaybillModelArray['partner_other_fee']);
            $worksheet->setCellValue('S' . $contextRow, $seaWaybillModelArray['partner_other_fee_desc']);
            $worksheet->setCellValue('T' . $contextRow, $seaWaybillModelArray['partner_stay_pole']);
            // $worksheet->setCellValue('T' . $contextRow, $seaWaybillModelArray['car']['number'] ?? '');

            $totalpartner_towing_fee = Tools::add($totalpartner_towing_fee, $seaWaybillModelArray['partner_towing_fee']);
            $totalpartner_overdue_fee = Tools::add($totalpartner_overdue_fee, $seaWaybillModelArray['partner_overdue_fee']);
            $totalpartner_stockpiling_fee = Tools::add($totalpartner_stockpiling_fee, $seaWaybillModelArray['partner_stockpiling_fee']);
            $totalpartner_huandan_fee = Tools::add($totalpartner_huandan_fee, $seaWaybillModelArray['partner_huandan_fee']);
            $totalpartner_thc_fee = Tools::add($totalpartner_thc_fee, $seaWaybillModelArray['partner_thc_fee']);
            $totalpartner_print_fee = Tools::add($totalpartner_print_fee, $seaWaybillModelArray['partner_print_fee']);
            $totalpartner_clean_fee = Tools::add($totalpartner_clean_fee, $seaWaybillModelArray['partner_clean_fee']);
            $totalpartner_other_fee = Tools::add($totalpartner_other_fee, $seaWaybillModelArray['partner_other_fee']);
            $totalpartner_stay_pole = Tools::add($totalpartner_stay_pole, $seaWaybillModelArray['partner_stay_pole']);
            ++$contextRow;
            ++$dataIndex;
        }

        $totalFee = Tools::add($totalFee, $totalpartner_towing_fee);
        $totalFee = Tools::add($totalFee, $totalpartner_overdue_fee);
        $totalFee = Tools::add($totalFee, $totalpartner_stockpiling_fee);
        $totalFee = Tools::add($totalFee, $totalpartner_huandan_fee);
        $totalFee = Tools::add($totalFee, $totalpartner_thc_fee);
        $totalFee = Tools::add($totalFee, $totalpartner_print_fee);
        $totalFee = Tools::add($totalFee, $totalpartner_clean_fee);
        $totalFee = Tools::add($totalFee, $totalpartner_other_fee);
        $worksheet->setCellValue('K' . $contextRow, $totalpartner_towing_fee);
        $worksheet->setCellValue('L' . $contextRow, $totalpartner_overdue_fee);
        $worksheet->setCellValue('M' . $contextRow, $totalpartner_stockpiling_fee);
        $worksheet->setCellValue('N' . $contextRow, $totalpartner_huandan_fee);
        $worksheet->setCellValue('O' . $contextRow, $totalpartner_thc_fee);
        $worksheet->setCellValue('P' . $contextRow, $totalpartner_print_fee);
        $worksheet->setCellValue('Q' . $contextRow, $totalpartner_clean_fee);
        $worksheet->setCellValue('R' . $contextRow, $totalpartner_other_fee);
        ++$contextRow;
        $worksheet->setCellValue('J' . $contextRow, '总计');
        $worksheet->setCellValue('K' . $contextRow, $totalFee);
        ++$contextRow;
        ++$contextRow;
        $worksheet->setCellValue('H' . $contextRow, '运费请付');
        ++$contextRow;
        $worksheet->setCellValue('H' . $contextRow, '开户行：中国建设银行股份有限公司深圳光明支行');
        ++$contextRow;
        $worksheet->setCellValue('H' . $contextRow, '户    名：李国欣');
        ++$contextRow;
        $worksheet->setCellValue('H' . $contextRow, '账    号：6217007200077503871');
        // 自己的车：同一天出车，奖励50
        // 总费用(总产值)=派车费+二次出车+其他费用(洗柜+路费+高速路费+扫把+黄油)
        // 船公司：拖车费
        // self::formatArray($worksheet, $data);
        return SpreadsheetService::exportExcelByTianchang($spreadsheet, $filename);
    }
}
