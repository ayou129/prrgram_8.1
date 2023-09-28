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
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class ShipCompanyBillStragegy implements BillExportStrategyInterface
{
    public function export($model)
    {
        // 1.船公司：拖车费
        $seaWaybillModels = WuliuSeaWaybill::where('ship_company_bill_id')->get();

        $spreadsheet = new Spreadsheet();
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        // 文档创建者
        $spreadsheet->getProperties()->setCreator('Liguoxin');
        // 上一次文档修改者
        $spreadsheet->getProperties()->setLastModifiedBy('Liguoxin');
        // 文档标题
        $spreadsheet->getProperties()->setTitle($model->title);
        // 文档主题
        $spreadsheet->getProperties()->setSubject('对账单');
        // 文档描述（备注）
        $spreadsheet->getProperties()->setDescription('船公司对账单');
        // 文档关键字
        // $spreadsheet->getProperties()->setKeywords('office 2007 openxml php');
        // 文档类别
        // $spreadsheet->getProperties()->setCategory('Test result file');
        // 行高
        // $spreadsheet->getActiveSheet()->setRowHeight(20);

        // 如果要直接输出文件而不保存在服务器上，要设置 header 头，并将文件放到 PHP 的缓冲区中
        header('Content-Type: application/vnd.ms-excel'); // 设置文件类型
        header("Content-Disposition: attachment;filename=\"{$model->title}.xlsx\""); // 设置文件名称及文件后缀，文件名可使用变量替代。
        header('Cache-Control: max-age=0'); // 设置缓存时间，此处为0不缓存
        foreach ($seaWaybillModels as $key => $value) {
            if ($key === 0) {
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(1, 1, '学生成绩表');
            }
            if ($key === 1) {
                // towing_fee
                // 序号 运单号 船名航次 箱型 箱号 费用科目(拖车费) 管理费(0.06) 实付金额 完成时间 进口出口 详细地址
                // 拖车费总额 管理费总额 实付金额
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(1, $key, '序号');
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(2, $key, '运单号');
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(3, $key, '船名航次');
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(4, $key, '箱型');
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(5, $key, '箱号');
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(6, $key, '拖车费');
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(7, $key, '管理费(0.06)');
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(8, $key, '实付金额');
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(9, $key, '完成时间');
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(10, $key, '进口出口');
                $spreadsheet->getActiveSheet()->setCellValueByColumnAndRow(11, $key, '详细地址');
            }
        }

        // 合并单元格
        $spreadsheet->getActiveSheet()->mergeCells('A1:E1');
        $writer->save('php://output');
        // 写入完成后，释放内存
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);
        return true;
    }
}
