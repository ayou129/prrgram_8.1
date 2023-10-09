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

namespace App\Service\Utils\Redis;

use Hyperf\Utils\ApplicationContext;

class Redis
{
    /**
     * @var \Hyperf\Redis\Redis
     */
    protected $redis;

    public function __construct()
    {
        $container = ApplicationContext::getContainer();
        $this->redis = $container->get(\Hyperf\Redis\Redis::class);
    }

    public function getRedis()
    {
        return $this->redis;
    }

    public function setUserConsoleLog($params)
    {
        $data = [
            'context' => $params['context'],
        ];
        // 存储到redis
        if ($params['user_id']) {
            $key = 'console_log|uid:' . (string) $params['user_id'];
        } else {
            $key = 'console_log|uip:' . (string) $params['ip'];
        }
        return $this->redis->hset($key, (string) microtime(true), json_encode($data));
    }

    public function getOptionsOrderPageShowZplaywShareBtn(): bool
    {
        $key = 'options|order_page_show_zplayw_share_btn';
        $v = $this->redis->get($key);
        //        var_dump($v);
        if ($v === false) {
            $this->redis->set($key, 1);
            return true;
        }
        return (bool) $this->redis->get($key);
    }

    public function getOptionsForceUpdateUserPrivacyAgreement(): bool
    {
        $key = 'options|force_update_user_privacy_agreement';
        $v = $this->redis->get($key);
        //        var_dump($v);
        if ($v === false) {
            $this->redis->set($key, 1);
            return true;
        }
        return (bool) $this->redis->get($key);
    }

    public function getOptionsUserAvatarUrl(): string
    {
        $key = 'options|user_avatar_url';
        $v = $this->redis->get($key);
        //        var_dump($v);
        if ($v === false) {
            $v = 'https://api.playwreport.tianchang56.com/miniprogram/v1/static/点击1.gif';
            $this->redis->set($key, $v);
            return $v;
        }
        return $this->redis->get($key);
    }
}
