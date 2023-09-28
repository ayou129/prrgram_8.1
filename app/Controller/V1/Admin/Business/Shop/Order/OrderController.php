<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Controller\V1\Admin\Business\Shop\Order;

use App\Constant\ServiceCode;

class OrderController
{
    public $xService;

    public function list()
    {
        // return $this->responseJson(ServiceCode::SUCCESS, $this->xService->list($this->request->all()));
    }
}
