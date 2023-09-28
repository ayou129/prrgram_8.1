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
 * 小程序.
 */
class MiniProgramService extends WechatService implements WechatServiceInterface
{
    public function __construct()
    {
        parent::__construct('miniProgram');
    }
}
