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

class WuliuSpreadsheetService extends SpreadsheetService
{
    private Spreadsheet $spreadsheet;

    private array $celsArray;

    private string $filename;

    private int $currentRow;

    private array $contextFirstRow;

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->spreadsheet->getDefaultStyle()->getFont()->setName('微软雅黑');
    }

    public static function getUtf8Context($spreadsheet, $from_encoding = 'iso-8859-1')
    {
        $worksheet = $spreadsheet->getActiveSheet();
        foreach ($worksheet->getRowIterator() as $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(false);
            foreach ($cellIterator as $cell) {
                $cellValue = $cell->getValue();
                if (! empty($cellValue) && ! is_integer($cellValue)) {
                    switch ($from_encoding) {
                        case 'iso-8859-1':
                            $cellValue = utf8_encode($cellValue);
                            break;
                        default:
                            $cellValue = iconv($from_encoding, 'utf-8', $cellValue);
                            break;
                    }
                    // $cellValue = mb_convert_encoding($cellValue, 'utf-8', $from_encoding);
                    $cell->setValue($cellValue);
                }
            }
        }
        return $spreadsheet;
    }

    public function juzhong()
    {
        $styleArray = [
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
            ],
        ];
        $this->spreadsheet->getActiveSheet()->getStyle('A2:R2')->applyFromArray($styleArray);
    }

    public function hebing()
    {
        $this->spreadsheet->getActiveSheet()->mergeCells('A1:R1');
    }

    public function setPropertCreator($value)
    {
        $this->spreadsheet->getProperties()->setCreator($value);
    }

    public function setLastModifiedBy($value)
    {
        $this->spreadsheet->getProperties()->setLastModifiedBy($value);
    }

    public function setHeader(array $array)
    {
    }

    public function setFilename(string $filename)
    {
        $this->filename = $filename;
    }

    public function setContextFirstRow(array $array)
    {
        foreach ($array as $key => $value) {
            $this->celsArray[] = $this->int3Excel($key);
        }
        $this->contextFirstRow = $array;
    }

    /**
     * 设置自动宽度.
     */
    public function setAutoSize()
    {
        foreach ($this->celsArray as $key => $value) {
            $this->spreadsheet->getActiveSheet()->getColumnDimension($value)->setAutoSize(true);
        }
    }

    /**
     * 获取数据.
     */
    public function getExportData()
    {
        $suffix = 'xlsx';
        $path = BASE_PATH . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'download' . DIRECTORY_SEPARATOR;
        $outFilename = $path . $this->filename . '.' . $suffix;
        $writer = IOFactory::createWriter($this->spreadsheet, ucfirst($suffix));
        // $writer->setUseBOM(true);
        $writer->save($outFilename);
        $this->close();
        return ['path' => $outFilename, 'filename' => $this->filename . '.' . $suffix];
    }

    private function close()
    {
        $this->spreadsheet->disconnectWorksheets();
        unset($this->spreadsheet);
    }
}
