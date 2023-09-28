<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service\Utils\Wechat;

/**
 * 开放平台.
 */
class OpenPlatformService extends WechatService implements WechatServiceInterface
{
    public function __construct()
    {
        parent::__construct('openPlatform');
    }
}
