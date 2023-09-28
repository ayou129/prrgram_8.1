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

namespace App\Service\Business\PlaywReport\StairPoint;

interface StairPointDiscountDetailsStrategyInterface
{
    /**
     * @return array['discount' => float, 'discount_price' => float, 'discount_details' => array]
     */
    public function getDiscountDetails(float $singlePrice): array;
}
