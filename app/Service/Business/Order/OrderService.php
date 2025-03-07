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

namespace App\Service\Business\Order;

use Hyperf\Di\Annotation\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;

class OrderService
{
    #[Inject]
    private EventDispatcherInterface $eventDispatcher;

    public function create()
    {
        // $orderModel = new Order();
        // $this->eventDispatcher->dispatch(new OrderCreated($orderModel));
    }
}
