<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

use App\Service\Utils\Redis\PlaywReport\McUserPlatform;
use Hyperf\Database\Model\Model;

/**
 * @property int $id
 * @property int $u_id
 * @property int $platform
 * @property string $wx_openid
 * @property string $wx_session_key
 * @property string $login_token
 * @property string $login_token_expire_time
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property mixed $user
 */
class UserPlatform extends BaseModel
{
    public const PLATFORM_MINIPROGRAM = 1;

    /**
     * @var null|Model|User
     */
    public ?User $user;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user_platform';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'u_id', 'platform', 'wx_openid', 'wx_session_key', 'login_token', 'login_token_expire_time', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'u_id' => 'integer', 'platform' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public static function getPlatformArray()
    {
        return [self::PLATFORM_MINIPROGRAM => '小程序'];
    }

    //    public function getUserAttribute()
    //    {
    //        return $this->u_id ? User::getCacheById($this->u_id) : null;
    //    }
    public function setUserAttribute()
    {
        unset($this->attributes['user']);
    }

    public static function getCacheById($k, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mcUserPlatform = new McUserPlatform($redis);
        $cache = $mcUserPlatform->getModel($k);
        if ($cache) {
            $model = (new self())->newInstance($cache, true);
        } else {
            $model = (new self())->where('id', $k)->first();
        }
        if ($model) {
            if (in_array('user', $relations)) {
                $model->user = User::getCacheById($model->u_id);
            }
        }
        return $model;
    }

    /**
     * @param mixed $relations
     * @param mixed $k
     * @param mixed $k2
     * @return null|\Hyperf\Database\Model\Builder|Model|object
     */
    public static function getCacheByTokenAndPlatform($k, $k2, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McUserPlatform($redis);
        $id = $mc->getByPlatformAndLoginToken($k, $k2);
        if ($id) {
            $cache = $mc->getModel($id);
            if ($cache) {
                $model = (new self())->newInstance($cache, true);
            } else {
                $model = null;
            }
        } else {
            $model = (new self())->where('platform', $k)->where('login_token', $k2)->first();
        }
        if ($model) {
            if (in_array('user', $relations)) {
                $model->user = User::getCacheById($model->u_id) ?? null;
            }
        }
        return $model;
    }

    public static function getCacheByUserIdAndPlatform($k, $k2, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McUserPlatform($redis);
        $id = $mc->getByPlatformAndUserId($k, $k2);
        if ($id) {
            $cache = $mc->getModel($id);
            if ($cache) {
                $model = (new self())->newInstance($cache, true);
            } else {
                $model = null;
            }
        } else {
            $model = (new self())->where('id', $k2)->first();
        }
        if ($model) {
            if (in_array('user', $relations)) {
                $model->user = User::getCacheById($model->u_id) ?? null;
            }
        }
        return $model ?? null;
    }

    public static function getCacheByWxPlatformAndUserIdAndOpenid($k, $k2, $k3, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McUserPlatform($redis);
        $id = $mc->getByPlatformAndUserIdAndWxOpenid($k, $k2, $k3);
        if ($id) {
            $cache = $mc->getModel($id);
            if ($cache) {
                $model = (new self())->newInstance($cache, true);
            } else {
                $model = null;
            }
        } else {
            $model = (new self())->where('platform', $k)->where('u_id', $k2)->where('wx_openid', $k3)->first();
        }
        if ($model) {
            if (in_array('user', $relations)) {
                $model->user = User::getCacheById($model->u_id) ?? null;
            }
        }
        return $model ?? null;
    }
}
