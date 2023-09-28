<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service\Business\Wuliu\Bill;

use App\Model\WuliuBill;

interface BillExportStrategyInterface
{
    public function export(WuliuBill $model);
}
