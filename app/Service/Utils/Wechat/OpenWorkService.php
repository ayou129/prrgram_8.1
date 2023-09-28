<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service\Utils\Wechat;

/**
 * 企业微信开放平台.
 */
class OpenWorkService extends WechatService implements WechatServiceInterface
{
    public function __construct()
    {
        parent::__construct('openWork');
    }
}
