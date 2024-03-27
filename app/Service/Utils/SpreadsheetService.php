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

namespace App\Service\Utils;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class SpreadsheetService
{

    public static function genExcelByTianchang(): Spreadsheet
    {
        return new Spreadsheet();
    }

    public static function exportExcelByTianchang($spreadsheet, $filename, $suffix = 'csv'): array
    {
        $path = BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'download' . DIRECTORY_SEPARATOR;
        $outFilename = $path . $filename . '.' . $suffix;
        $writer = IOFactory::createWriter($spreadsheet, ucfirst($suffix));
        $writer->save($outFilename);
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        return ['path' => $outFilename, 'filename' => rawurlencode($filename . '.' . $suffix)];
    }

    public function excel()
    {
        // 在单元格中设置公式 Set cell A4 with a formula
        $spreadsheet->getActiveSheet()->setCellValue(
            'A4',
            '=IF(A3, CONCATENATE(A1, " ", A2), CONCATENATE(A2, " ", A1))'
        );
        $spreadsheet->getActiveSheet()->getCell('A4')
            ->getStyle()->setQuotePrefix(true);

        // 在单元格中设置日期和/或时间值
        // Get the current date/time and convert to an Excel date/time
        $dateTimeNow = time();
        $excelDateValue = \PhpOffice\PhpSpreadsheet\Shared\Date::PHPToExcel($dateTimeNow);
        // Set cell A6 with the Excel date/time value
        $spreadsheet->getActiveSheet()->setCellValue(
            'A6',
            $excelDateValue
        );
        // Set the number format mask so that the excel timestamp will be displayed as a human-readable date/time
        $spreadsheet->getActiveSheet()->getStyle('A6')
            ->getNumberFormat()
            ->setFormatCode(
                \PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_DATE_DATETIME
            );

        // 按坐标 获取 单元格值
        $cellValue = $spreadsheet->getActiveSheet()->getCell('A1')->getValue();
        // 如果是公式，只要计算值
        $cellValue = $spreadsheet->getActiveSheet()->getCell('A4')->getCalculatedValue();
        // 按列和行设置单元格值
        // Set cell A5 with a string value
        $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(1, 5, 'PhpSpreadsheet');

        // 按列和行 获取 单元格值
        // Get the value from cell B5
        $cellValue = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(2, 5)->getValue();
        // 如果是公式，只要计算值
        // Get the value from cell A4
        $cellValue = $spreadsheet->getActiveSheet()->getCellByColumnAndRow(1, 4)->getCalculatedValue();

        // 将一系列单元格值 获取 到数组
        $dataArray = $spreadsheet->getActiveSheet()
            ->rangeToArray(
                'C3:E5',     // 工作表范围 The worksheet range that we want to retrieve
                null,        // 空单元格返回的值 Value that should be returned for empty cells
                true,        // 是否只要计算值 Should formulas be calculated (the equivalent of getCalculatedValue() for each cell)
                true,        // 是否格式化值 Should values be formatted (the equivalent of getFormattedValue() for each cell)
                true         // 数组是否应按单元格行和单元格列进行索引 Should the array be indexed by cell row and cell column
            );
    }

    public function genPdf()
    {
        // Dompdf, Mpdf or Tcpdf (as appropriate)
        $className = \PhpOffice\PhpSpreadsheet\Writer\Pdf\Dompdf::class;
        IOFactory::registerWriter('Pdf', $className);
    }

    public function int3Excel($num)
    {
        if ($num > 26) {
            $count = floor($num / 26);
            $count2 = $num % 26;
            $a = $this->int3Excel($count - 1);
            $b = $this->int2Excel($count2);
            return $a . $b;
        }
        return $this->int2Excel($num);
    }

    private function int2Excel($num)
    {
        $m = (int) ($num % 26);
        $q = (int) ($num / 26);
        $letter = chr(ord('A') + $m);
        if ($q > 0) {
            return $this->int2Excel($q - 1) . $letter;
        }
        return $letter;
    }
}
