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
    private array $config = [];

    private float $price_total = 0;

    public function __construct()
    {
        $modelCommentArray = WuliuSeaWaybill::getAttributeComment();
        $this->config = [
            'number' => ['auto_width' => true],
            'case_number' => ['auto_width' => true],
            'qf_number' => ['auto_width' => true],
            'good_name' => ['auto_width' => true],
            'liaison_address_detail' => [],
            'car_number' => ['label' => '车牌号'],
            'car_finished_date' => ['auto_width' => true],
            'car_fee' => ['is_collect' => true, 'is_collect_price' => true],
            'car_other_fee' => ['is_collect' => true, 'is_collect_price' => true],
            'car_other_fee_desc' => [],
        ];

        $this->config = array_merge(['index' => ['label' => '序号']], $this->config);

        # 动态赋值 config 的 label
        $index = 0;
        foreach ($this->config as $key => &$value) {
            if (! isset($value['label'])) {
                $value['label'] = $modelCommentArray[$key];
            }
            if (isset($value['is_collect'])) {
                $value['is_collect_all'] = 0;
            }
            if (isset($value['is_collect_price'])) {
                $value['is_collect_price_all'] = 0;
            }

            # 列名ABCD...
            $value['col'] = Tools::genExcelColNameFromArrayIndex($index);
            ++$index;
        }
    }

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
        $worksheet->setCellValue('A1', Tools::formatUtf8(env('APP_NAME', '') . $model->title . '对账单'));
        $spreadsheet->getActiveSheet()->getStyle('A1')->getFont()->setBold(false)
            ->setSize(16);
        $worksheet->getStyle('A1:J1')->applyFromArray($styleArray);
        $worksheet->mergeCells('A1:J1');

        $contextRow = 1;

        # 标题
        foreach ($this->config as $fieldConfig) {
            $location = $fieldConfig['col'] . (string) $contextRow;
            $worksheet->setCellValue($location, Tools::formatUtf8($fieldConfig['label']));

            # 设置标题和宽度
            if (isset($fieldConfig['auto_width'])) {
                $worksheet->getColumnDimension($fieldConfig['col'])->setAutoSize(true);
            }
        }
        ++$contextRow;

        # 数据
        $dataCount = 1;
        foreach ($seaWaybillModelsArray as $keys => $seaWaybillModelArray) {
            foreach ($seaWaybillModelArray as $field => $value) {
                if ($field == 'car') {
                    $field = 'car_number';
                    $value = $value['number'] ?? '';
                }

                if (isset($this->config[$field])) {
                    $fieldConfig = $this->config[$field];

                    # 序号
                    $worksheet->setCellValue('A' . (string) $contextRow, Tools::formatUtf8($dataCount));

                    # 数据
                    $location = $fieldConfig['col'] . (string) $contextRow;
                    $worksheet->setCellValue($location, Tools::formatUtf8($value));

                    if (isset($fieldConfig['is_collect'])) {
                        $this->config[$field]['is_collect_all'] = Tools::add($this->config[$field]['is_collect_all'], $value);
                    }

                    if (isset($fieldConfig['is_collect_price'])) {
                        $this->config[$field]['is_collect_price_all'] = Tools::add($this->config[$field]['is_collect_price_all'], $value);
                    }
                }
            }
            ++$dataCount;
            ++$contextRow;
        }

        $is_collect = false;
        # 处理汇总的数据
        foreach ($this->config as $fieldConfig) {
            if (isset($fieldConfig['is_collect'])) {
                $worksheet->setCellValue($fieldConfig['col'] . (string) $contextRow, Tools::formatUtf8($fieldConfig['is_collect_all']));
                $is_collect = true;

                # 处理总价格
                if (isset($fieldConfig['is_collect_price'])) {
                    $this->price_total = Tools::add($this->price_total, Tools::formatUtf8($fieldConfig['is_collect_price_all']));
                }
            }
        }
        if ($is_collect) {
            ++$contextRow;
        }

        ++$contextRow;
        $worksheet->setCellValue('J' . $contextRow, Tools::formatUtf8('总计'));
        $worksheet->setCellValue('K' . $contextRow, $this->price_total);
        ++$contextRow;
        ++$contextRow;
        $worksheet->setCellValue('H' . $contextRow, Tools::formatUtf8('运费请付'));
        ++$contextRow;
        $worksheet->setCellValue('H' . $contextRow, Tools::formatUtf8('开户行：中国建设银行股份有限公司深圳光明支行'));
        ++$contextRow;
        $worksheet->setCellValue('H' . $contextRow, Tools::formatUtf8('户    名：李国欣'));
        ++$contextRow;
        $worksheet->setCellValue('H' . $contextRow, Tools::formatUtf8('账    号：6217007200077503871'));

        return SpreadsheetService::exportExcelByTianchang($spreadsheet, $filename);
    }
}
