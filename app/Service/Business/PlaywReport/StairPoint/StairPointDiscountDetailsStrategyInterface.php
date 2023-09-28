<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service\Business\PlaywReport\StairPoint;

interface StairPointDiscountDetailsStrategyInterface
{
    /**
     * @return array['discount' => float, 'discount_price' => float, 'discount_details' => array]
     */
    public function getDiscountDetails(float $singlePrice): array;
}
