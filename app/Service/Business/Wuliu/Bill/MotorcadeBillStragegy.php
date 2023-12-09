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
use App\Utils\Tools;

class MotorcadeBillStragegy implements BillExportStrategyInterface
{
    public function export($model)
    {
        // 2.车队账单：多个车辆派车费
        $seaWaybillModels = WuliuSeaWaybill::where('motorcade_bill_id', $model->id)
            ->where('self_bill_id', '=', null)
            ->orderBy('car_id', 'asc')
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
        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑');
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setCellValue('A1', env('APP_NAME', '') . $model->title . '对账单');
        $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setBold(false)
            ->setSize(16);
        $worksheet->getStyle('A1:J1')->applyFromArray($styleArray);
        $worksheet->mergeCells('A1:J1');

        $worksheet->getStyle('A2:J2')->applyFromArray($styleArray);
        $worksheet->getRowDimension(1)->setRowHeight(50);
        $worksheet->getColumnDimension('B')->setWidth(15);
        $worksheet->getColumnDimension('C')->setWidth(22);
        $worksheet->getColumnDimension('D')->setWidth(15);
        $worksheet->getColumnDimension('E')->setWidth(35);
        $worksheet->getColumnDimension('F')->setWidth(10);
        $worksheet->getColumnDimension('G')->setWidth(7);
        $worksheet->getColumnDimension('H')->setWidth(8);
        $worksheet->getColumnDimension('I')->setWidth(11);
        $worksheet->getColumnDimension('J')->setWidth(15);
        $contextRow = 2;
        $worksheet->setCellValue('A' . $contextRow, '序号');
        $worksheet->setCellValue('B' . $contextRow, '派车日期');
        $worksheet->setCellValue('C' . $contextRow, '运单号');
        $worksheet->setCellValue('D' . $contextRow, '箱号');
        $worksheet->setCellValue('E' . $contextRow, '地址');
        $worksheet->setCellValue('F' . $contextRow, '货名');
        $worksheet->setCellValue('G' . $contextRow, '拖车费');
        $worksheet->setCellValue('H' . $contextRow, '其他费用');
        $worksheet->setCellValue('I' . $contextRow, '其他费用说明');
        $worksheet->setCellValue('J' . $contextRow, '车牌号');
        $total = $totalCarFee = $totalCarOtherFee = 0;
        $dataIndex = 1;
        $contextRow = 3;
        foreach ($seaWaybillModelsArray as $seaWaybillModelArray) {
            $worksheet->setCellValue('A' . $contextRow, $dataIndex);
            $worksheet->setCellValue('B' . $contextRow, $seaWaybillModelArray['car_finished_date']);
            $worksheet->setCellValue('C' . $contextRow, $seaWaybillModelArray['number']);
            $worksheet->setCellValue('D' . $contextRow, $seaWaybillModelArray['case_number']);
            $worksheet->setCellValue('E' . $contextRow, $seaWaybillModelArray['liaison_address_detail']);
            $worksheet->setCellValue('F' . $contextRow, $seaWaybillModelArray['good_name']);
            $worksheet->setCellValue('G' . $contextRow, $seaWaybillModelArray['car_fee']);
            $worksheet->setCellValue('H' . $contextRow, $seaWaybillModelArray['car_other_fee']);
            $worksheet->setCellValue('I' . $contextRow, $seaWaybillModelArray['car_other_fee_desc']);
            $worksheet->setCellValue('J' . $contextRow, $seaWaybillModelArray['car']['number']);
            $totalCarFee = Tools::add($totalCarFee, $seaWaybillModelArray['car_fee']);
            $totalCarOtherFee = Tools::add($totalCarOtherFee, $seaWaybillModelArray['car_other_fee']);
            ++$contextRow;
            ++$dataIndex;
        }
        $worksheet->setCellValue('G' . $contextRow, $totalCarFee);
        $worksheet->setCellValue('H' . $contextRow, $totalCarOtherFee);
        ++$contextRow;
        ++$contextRow;
        $total = Tools::add($totalCarFee, $totalCarOtherFee);
        $worksheet->setCellValue('F' . $contextRow, '应付总额');
        $worksheet->setCellValue('G' . $contextRow, $total);

        return SpreadsheetService::exportExcelByTianchang($spreadsheet, $filename);
    }
}
