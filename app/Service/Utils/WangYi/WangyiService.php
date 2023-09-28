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

namespace App\Service\Utils\WangYi;

use App\Utils\Im\WangyiIM;
use Hyperf\Di\Annotation\Inject;

class WangyiService
{
    /**
     * @Inject
     * @var WangyiIM
     */
    protected $wangyiIM;

    public function success($modify_response = [], $callback_ext = '')
    {
        return $this->wangyiIM->success($modify_response, $callback_ext);
    }

    public function fail($response_code, array $modify_response = [], string $callback_ext = '')
    {
        return $this->wangyiIM->fail($response_code, $modify_response, $callback_ext);
    }

    public function callback($params)
    {
        # 校验
        $params = [
            'body',
            'eventType',
            'fromAccount',
            'fromClientType',
            'fromDeviceId',
            'fromNick',
            'msgTimestamp',
            'msgType',
            'msgidClient',
            'to',
            'fromClientIp',
            'fromClientPort',
        ];
        $this->wangyiIM->checkAppid($params['AppKey'], $params['CurTime'], $params['MD5'], $params['CheckSum'], $params['Body']);
        if (empty($get['SdkAppid']) || ! $dim->checkAppid($get['SdkAppid']) || empty($get['CallbackCommand'])) {
            return $response;
        }
        # 处理核心内容
    }

    public function createAccid($accid, $name)
    {
        return $this->wangyiIM->createAccid($accid, $name);
    }

    public function login()
    {
    }

    public function refreshToken()
    {
    }
}
