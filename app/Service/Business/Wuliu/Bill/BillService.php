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

use App\Model\WuliuBill;
use LogicException;

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
                throw new LogicException('不支持的类型');
        }
        return $this->exportContext->export($model);
    }
}
