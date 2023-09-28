<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service\Business\Wuliu\Bill;

use App\Model\WuliuBill;

class BillService
{
    // private WuliuBill $model;

    private BillExportStrategyInterface $exportContext;

    public function __construct()
    {
        // $this->model = $model;
    }

    public function export(WuliuBill $model)
    {
        if ($model->type !== WuliuBill::STATUS_DEFAULT) {
            // throw new \LogicException('该账单状态已确认，无法进行更改');
        }
        switch ($model->type) {
            case WuliuBill::TYPE_SHIP_COMPANY:
                $this->exportContext = new ShipCompanyBillStragegy();
                break;
            case WuliuBill::TYPE_MOTORCADE:
                $this->exportContext = new MotorcadeBillStragegy();
                break;
            case WuliuBill::TYPE_PARTNER:
                $this->exportContext = new PartnerBillStragegy();
                break;
            case WuliuBill::TYPE_SELF:
                $this->exportContext = new SelfBillStragegy();
                break;
            default:
                throw new \LogicException('不支持的类型');
        }
        return $this->exportContext->export($model);
    }
}
