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

namespace App\Service\Utils\Redis\PlaywReport;

use App\Model\UserPlatform;
use App\Service\Utils\Redis\PlaywReport\MCStrategy\MCStrategyAbstract;
use Hyperf\Redis\Redis;

class McUserPlatform extends MCStrategyAbstract
{
    public const ttl = 3600 * 24 * 2;

    public string $table = '';

    public function __construct(?Redis $redis)
    {
        $this->table = (new UserPlatform())->getTable();
        parent::__construct($redis);
    }

    public function setByPlatformAndByLoginToken($platform, $login_token, $id)
    {
        $key = self::getByPlatformAndLoginTokenKey($platform, $login_token);
        return $this->set($key, $id, ['ex' => self::ttl]);
    }

    public function getByPlatformAndLoginToken($platform, $login_token)
    {
        $key = self::getByPlatformAndLoginTokenKey($platform, $login_token);
        return $this->get($key);
    }

    public function delByPlatformAndLoginToken($platform, $login_token)
    {
        $key = self::getByPlatformAndLoginTokenKey($platform, $login_token);
        return $this->del($key);
    }

    public function getByPlatformAndLoginTokenKey($platform, $login_token): string
    {
        return $this->prefix . $this->table . '|platform:' . $platform . '|login_token:' . $login_token;
    }

    public function setByPlatformAndUserIdAndWxOpenid($platform, $u_id, $wx_openid, $id)
    {
        $key = self::getByPlatformAndUserIdAndWxOpenidKey($platform, $u_id, $wx_openid);
        return $this->set($key, $id, ['ex' => self::ttl]);
    }

    public function getByPlatformAndUserIdAndWxOpenid($platform, $u_id, $wx_openid)
    {
        $key = self::getByPlatformAndUserIdAndWxOpenidKey($platform, $u_id, $wx_openid);
        return $this->get($key);
    }

    public function delByPlatformAndUserIdAndWxOpenid($platform, $u_id, $wx_openid)
    {
        $key = self::getByPlatformAndUserIdAndWxOpenidKey($platform, $u_id, $wx_openid);
        return $this->del($key);
    }

    public function getByPlatformAndUserIdAndWxOpenidKey($platform, $u_id, $wx_openid): string
    {
        return $this->prefix . $this->table . '|platform:' . $platform . '|u_id:' . $u_id . '|wx_openid:' . $wx_openid;
    }

    public function setByPlatformAndWxOpenid($platform, $wx_openid, $id)
    {
        $key = self::getByPlatformAndWxOpenidKey($platform, $wx_openid);
        return $this->set($key, $id, ['ex' => self::ttl]);
    }

    public function getByPlatformAndWxOpenid($platform, $wx_openid)
    {
        $key = self::getByPlatformAndWxOpenidKey($platform, $wx_openid);
        return $this->get($key);
    }

    public function delByPlatformAndWxOpenid($platform, $wx_openid)
    {
        $key = self::getByPlatformAndWxOpenidKey($platform, $wx_openid);
        return $this->del($key);
    }

    public function getByPlatformAndWxOpenidKey($platform, $wx_openid): string
    {
        return $this->prefix . $this->table . '|platform:' . $platform . '|wx_openid:' . $wx_openid;
    }

    public function setByPlatformAndUserId($platform, $user_id, $id)
    {
        $key = self::getByPlatformAndUserIdKey($platform, $user_id);
        return $this->set($key, $id, ['ex' => self::ttl]);
    }

    public function getByPlatformAndUserId($platform, $user_id)
    {
        $key = self::getByPlatformAndUserIdKey($platform, $user_id);
        return $this->get($key);
    }

    public function delByPlatformAndUserId($platform, $user_id)
    {
        $key = self::getByPlatformAndUserIdKey($platform, $user_id);
        return $this->del($key);
    }

    public function getByPlatformAndUserIdKey($platform, $user_id): string
    {
        return $this->prefix . $this->table . '|platform:' . $platform . '|user_id:' . $user_id;
    }
}
