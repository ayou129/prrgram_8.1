<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service\Order;

use App\Event\OrderCreated;
use Hyperf\Di\Annotation\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;

class OrderService
{
    /**
     * @Inject
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function create()
    {
        $orderModel = new Order();
        $this->eventDispatcher->dispatch(new OrderCreated($orderModel));
    }
}
