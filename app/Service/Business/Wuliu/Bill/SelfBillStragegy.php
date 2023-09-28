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

use App\Constant\ServiceCode;
use App\Model\WuliuSeaWaybill;
use App\Service\Utils\SpreadsheetService;
use App\Utils\Tools;
use Hyperf\HttpMessage\Exception\HttpException;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class SelfBillStragegy implements BillExportStrategyInterface
{
    public function export($model)
    {
        $seaWaybillModels = WuliuSeaWaybill::where('self_bill_id', $model->id)
            ->where('motorcade_bill_id', '=', null)
            ->orderBy('car_finished_date', 'asc')
            ->orderBy('number')
            ->get();
        $seaWaybillModelsArray = $seaWaybillModels->toArray();
        $filename = $model->title . '对账单';
        $spreadsheet = SpreadsheetService::genExcelByTianchang();
        $spreadsheet->getProperties()
            ->setCreator('Liguoxin')
            ->setLastModifiedBy('Liguoxin')
            ->setTitle($filename);
        // ->setSubject('Office 2007 XLSX Test Document')
        // ->setDescription('Test document for Office 2007 XLSX, generated using PHP classes.')
        // ->setKeywords('office 2007 openxml php')
        // ->setCategory('Test result file');
        // 总数 提成 杂费 第二车
        // 设置文字水平居中
        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑');
        $worksheet = $spreadsheet->getActiveSheet();
        $worksheet->setCellValue('A1', '阿尤陪玩报备' . $model->title . '对账单');
        $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setBold(false)
            ->setSize(16);
        $worksheet->getStyle('A1:J1')->applyFromArray($styleArray);
        $worksheet->mergeCells('A1:J1');

        $worksheet->getStyle('A2:J2')->applyFromArray($styleArray);
        $worksheet->getRowDimension(1)->setRowHeight(50);
        $worksheet->getColumnDimension('B')->setWidth(13);
        $worksheet->getColumnDimension('C')->setWidth(22);
        $worksheet->getColumnDimension('D')->setWidth(15);
        $worksheet->getColumnDimension('E')->setWidth(35);
        $worksheet->getColumnDimension('F')->setWidth(15);
        $worksheet->getColumnDimension('G')->setWidth(7);
        $worksheet->getColumnDimension('H')->setWidth(8);
        $worksheet->getColumnDimension('I')->setWidth(15);
        $worksheet->getColumnDimension('J')->setWidth(9);
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
        $worksheet->setCellValue('J' . $contextRow, '是否第二车');

        // context
        $contextRow = 3;
        // 按照派车时间排序，方便计算第二车情况
        // $car_finished_date_sort_array = [];
        // foreach ($seaWaybillModelsArray as $value) {
        //     $car_finished_date_sort_array[] = $value['car_finished_date'];
        // }
        // array_multisort($car_finished_date_sort_array, SORT_ASC, $seaWaybillModelsArray);
        $last_car_finished_date = false;
        $totalCarFee = $totalCarOtherFee = $totalTwoCar = '0';
        // var_dump($seaWaybillModelsArray);
        $dataIndex = 1;
        $two_car_index = 0;
        foreach ($seaWaybillModelsArray as $seaWaybillModelArray) {
            // 是否第二车
            $two_car = false;
            if (! $last_car_finished_date) {
                $last_car_finished_date = $seaWaybillModelArray['car_finished_date'];
            } else {
                if (Tools::isSameDays($last_car_finished_date, $seaWaybillModelArray['car_finished_date'])) {
                    ++$two_car_index;
                    $two_car = true;
                    $totalTwoCar += 50;
                }
            }
            if (! $seaWaybillModelArray['car_fee']) {
                // throw new HttpException(ServiceCode::HTTP_CLIENT_PARAM_ERROR, $seaWaybillModelArray['number'] . '-' . $seaWaybillModelArray['case_number'] . '派车费用不能是0');
            }
            if ($seaWaybillModelArray['car_finished_date']) {
                $last_car_finished_date = $seaWaybillModelArray['car_finished_date'];
            }
            $worksheet->setCellValue('A' . $contextRow, $dataIndex);
            $worksheet->setCellValue('B' . $contextRow, $seaWaybillModelArray['car_finished_date']);
            $worksheet->setCellValue('C' . $contextRow, $seaWaybillModelArray['number']);
            $worksheet->setCellValue('D' . $contextRow, $seaWaybillModelArray['case_number']);
            $worksheet->setCellValue('E' . $contextRow, $seaWaybillModelArray['liaison_address_detail']);
            $worksheet->setCellValue('F' . $contextRow, $seaWaybillModelArray['good_name']);
            $worksheet->setCellValue('G' . $contextRow, $seaWaybillModelArray['car_fee']);
            $worksheet->setCellValue('H' . $contextRow, $seaWaybillModelArray['car_other_fee']);
            $worksheet->setCellValue('I' . $contextRow, $seaWaybillModelArray['car_other_fee_desc']);
            $worksheet->setCellValue('J' . $contextRow, $two_car === true ? $two_car_index : '');
            $totalCarFee = Tools::add($totalCarFee, $seaWaybillModelArray['car_fee']);
            $totalCarOtherFee = Tools::add($totalCarOtherFee, $seaWaybillModelArray['car_other_fee']);
            ++$contextRow;
            ++$dataIndex;
        }
        $worksheet->setCellValue('F' . $contextRow, '总产值');
        $worksheet->setCellValue('G' . $contextRow, $totalCarFee);
        $worksheet->setCellValue('H' . $contextRow, $totalCarOtherFee);
        $worksheet->setCellValue('J' . $contextRow, $totalTwoCar);
        ++$contextRow;
        ++$contextRow;
        $tichenglv = 0.15;
        $dixin = 2600;
        $ticheng = Tools::mul($totalCarFee, $tichenglv);
        $worksheet->setCellValue('D' . $contextRow, '工资计算');
        $worksheet->setCellValue('E' . $contextRow, '提成=总产值X' . $tichenglv);
        $worksheet->setCellValue('F' . $contextRow, '提成');
        $worksheet->setCellValue('G' . $contextRow, $ticheng);
        ++$contextRow;
        $worksheet->setCellValue('F' . $contextRow, '底薪');
        $worksheet->setCellValue('G' . $contextRow, $dixin);
        ++$contextRow;
        $worksheet->setCellValue('F' . $contextRow, '其他费用总额');
        $worksheet->setCellValue('G' . $contextRow, $totalCarOtherFee);
        ++$contextRow;
        $worksheet->setCellValue('F' . $contextRow, '第二车总额');
        $worksheet->setCellValue('G' . $contextRow, $totalTwoCar);
        ++$contextRow;
        $totalAll = 0;
        $totalAll = Tools::add($totalAll, $ticheng);
        $totalAll = Tools::add($totalAll, $totalCarOtherFee);
        $totalAll = Tools::add($totalAll, $totalTwoCar);
        $totalAll = Tools::add($totalAll, $dixin);
        $worksheet->setCellValue('F' . $contextRow, '应付');
        $worksheet->setCellValue('G' . $contextRow, $totalAll);

        // 自己的车：同一天出车，奖励50
        // 总费用(总产值)=派车费+二次出车+其他费用(洗柜+路费+高速路费+扫把+黄油)
        // 船公司：拖车费
        // self::formatArray($worksheet, $data);
        return SpreadsheetService::exportExcelByTianchang($spreadsheet, $filename);
    }

    // public static function formatArray($sheet, $arr)
    // {
    //     try {
    //         foreach ($arr as $y => $list) {
    //             $list = array_values($list);
    //             foreach ($list as $x => $v) {
    //                 $crd = self::getCrd($x, $y);
    //                 $val = $v;
    //                 $sheet->setCellValue($crd, $val);
    //                 if (is_numeric($val) && strlen($val) >= 11) {
    //                     $sheet->getCell($crd)->setValueExplicit($val, DataType::TYPE_STRING);
    //                 }
    //             }
    //         }
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    // }

    // /**
    //  * 获取X轴坐标 A~Z.
    //  * @param int $index 下标
    //  * @return string
    //  */
    // public static function getXCrd($index = 0)
    // {
    //     $temp = '';
    //     $c = floor($index / 26);
    //     if ($c == 0 || $index == 25) {
    //         $temp .= chr($index + 65);
    //     } else {
    //         $y = $index % 26;
    //         $temp .= chr($c + 64) . chr($y + 65);
    //     }
    //     return $temp;
    // }

    // /**
    //  * 获取Y轴下标 1~∞.
    //  * @param int $index
    //  * @return int|mixed
    //  */
    // public static function getYCrd($index = 0)
    // {
    //     return ++$index;
    // }

    // /**
    //  * 获取坐标 X1~X∞.
    //  * @param mixed $xIndex
    //  * @param mixed $yIndex
    //  * @return string
    //  */
    // public static function getCrd($xIndex, $yIndex)
    // {
    //     return self::getXCrd($xIndex) . self::getYCrd($yIndex);
    // }
}
