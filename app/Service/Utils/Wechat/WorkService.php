<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service\Utils\Wechat;

class WorkService extends WechatService implements WechatServiceInterface
{
    public function __construct()
    {
        parent::__construct('work');
    }
}
