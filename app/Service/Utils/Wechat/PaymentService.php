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

namespace App\Service\Utils\Wechat;

/**
 * 微信支付.
 */
class PaymentService extends WechatService implements WechatServiceInterface
{
    public function __construct()
    {
        parent::__construct('payment');
    }
}
