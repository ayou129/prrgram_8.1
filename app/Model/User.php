<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

use App\Service\Utils\Redis\PlaywReport\McUser;
use Hyperf\Utils\Collection;
use HyperfExt\Jwt\Contracts\JwtSubjectInterface;

/**
 * @property int $id
 * @property string $phone
 * @property string $wx_unionid
 * @property string $password
 * @property string $real_name
 * @property string $nickname
 * @property string $avatar_image_id
 * @property int $gender
 * @property string $constellation
 * @property string $city
 * @property string $province
 * @property string $country
 * @property int $status
 * @property string $playw_report_playwname
 * @property int $playw_report_club_jiedan_price
 * @property int $playw_report_club_id
 * @property string $playw_report_club_join_at
 * @property int $playw_report_club_admin
 * @property string $social_dazzle_nickname
 * @property string $social_signature
 * @property int $social_charm_value
 * @property int $social_magnate_value
 * @property int $social_noble_value
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property \Hyperf\Database\Model\Collection|PlaywReportPlaywClubBoss[] $boss
 * @property PlaywReportClub $club
 * @property PlaywReportApply $clubJoinApply
 * @property PlaywReportApply $clubLeaveApply
 * @property mixed $label
 * @property mixed $onshow
 * @property UserPlatform $platform
 * @property \Hyperf\Database\Model\Collection|UserPlatform[] $platforms
 * @property mixed $birthday
 * @property mixed $social_id
 */
class User extends BaseModel implements JwtSubjectInterface
{
    public const PLAYW_REPORT_CLUB_ADMIN_DEFAULT = 0;

    public const PLAYW_REPORT_CLUB_ADMIN_YES = 1;

    public ?UserPlatform $platformMiniprogram;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'phone', 'wx_unionid', 'password', 'real_name', 'nickname', 'avatar_image_id', 'gender', 'birthday', 'constellation', 'city', 'province', 'country', 'status', 'playw_report_playwname', 'playw_report_club_jiedan_price', 'playw_report_club_id', 'playw_report_club_join_at', 'playw_report_club_admin', 'social_id', 'social_dazzle_nickname', 'social_signature', 'social_charm_value', 'social_magnate_value', 'social_noble_value', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'gender' => 'integer', 'status' => 'integer', 'playw_report_club_jiedan_price' => 'integer', 'playw_report_club_id' => 'integer', 'playw_report_club_admin' => 'integer', 'social_id' => 'integer', 'social_charm_value' => 'integer', 'social_magnate_value' => 'integer', 'social_noble_value' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected $hidden = ['password'];

    protected $appends = ['playw_report_club_admin_text', 'label', 'onshow'];

    public function getPlaywReportClubAdminArray()
    {
        return [self::PLAYW_REPORT_CLUB_ADMIN_DEFAULT => '否', self::PLAYW_REPORT_CLUB_ADMIN_YES => '是'];
    }

    public function setBirthdayAttribute($v)
    {
        $this->attributes['birthday'] = $v === '' ? null : $v;
    }

    public function setSocialIdAttribute($v)
    {
        $this->attributes['social_id'] = $v === '' ? null : $v;
    }

    public function getLabelAttribute()
    {
        return $this->playw_report_playwname ? $this->playw_report_playwname : '未设置昵称';
    }

    public function getOnshowAttribute()
    {
        return true;
    }

    public function getJwtIdentifier()
    {
        return $this->getKey();
    }

    public function getJwtCustomClaims(): array
    {
        return [];
    }

    public function platform()
    {
        return $this->hasOne(UserPlatform::class, 'u_id', 'id');
    }

    public function platforms()
    {
        return $this->hasMany(UserPlatform::class, 'u_id', 'id');
    }

    public function boss()
    {
        return $this->hasMany(PlaywReportPlaywClubBoss::class, 'u_id', 'id');
    }

    public function club()
    {
        return $this->hasOne(PlaywReportClub::class, 'id', 'playw_report_club_id');
    }

    // 关联一个apply type=club_leave的申请
    public function clubLeaveApply()
    {
        return $this->hasOne(PlaywReportApply::class, 'u_id', 'id')->where('status', PlaywReportApply::STATUS_DEFAULT)->where('type', PlaywReportApply::TYPE_CLUB_LEAVE);
    }

    public function clubJoinApply()
    {
        return $this->hasOne(PlaywReportApply::class, 'u_id', 'id')->where('status', PlaywReportApply::STATUS_DEFAULT)->where('type', PlaywReportApply::TYPE_CLUB_JOIN)->with('club');
    }

    public static function getCacheById($k, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McUser($redis);
        $cache = $mc->getModel($k);
        if ($cache) {
            $model = (new self())->newInstance($cache, true);
        } else {
            $model = (new self())->where('id', $k)->first();
        }
        self::addRelations($model, $relations);
        return $model ?? null;
    }

    public static function getCacheByIds($k, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McUser($redis);
        $cache = $mc->getModels($k);
        if ($cache) {
            $models = [];
            foreach ($cache as $item) {
                $models[] = (new self())->newInstance($item, true);
            }
            $models = $models ? new Collection($models) : new Collection([]);
        } else {
            $models = (new self())->whereIn('id', $k)->get();
        }
        foreach ($models as $model) {
            self::addRelations($model, $relations);
        }
        return $models;
    }

    public static function getCacheUserByPhone($k, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McUser($redis);
        $id = $mc->getByPhone($k);
        if ($id) {
            $cache = $mc->getModel($id);
            if ($cache) {
                $model = (new self())->newInstance($cache, true);
            } else {
                $model = null;
            }
        } else {
            $model = (new self())->where('phone', $k)->first();
        }
        self::addRelations($model, $relations);
        return $model ?? null;
    }

    /**
     * @param mixed $k
     * @param mixed $k2
     * @param mixed $relations
     * @return Collection
     */
    public static function getCacheBossListByIdAndClubId($k, $k2, $relations = [])
    {
        # TODO FIX
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McUser($redis);
        $cache = $mc->getBossListSortCreatedAtByClubIdAll($k, $k2);
        var_dump($cache, 'cac');
        if ($cache) {
            $models = PlaywReportPlaywClubBoss::getCacheByIds($cache);
        } else {
            $models = new Collection([]);
        }
        return $models;
    }

    public static function getCacheByBossIdAndClubId($k, $k2, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McUser($redis);
        $id = $mc->getBossL($k);
        if ($id) {
            $cache = $mc->getModel($id);
            if ($cache) {
                $model = (new self())->newInstance($cache, true);
            } else {
                $model = null;
            }
        } else {
            $model = (new self())->where('id', $k)->where('playw_report_club_id', $k2)->first();
        }
        self::addRelations($model, $relations);
        return $model ?? null;
    }

    public static function addRelations(&$model, $relations = [])
    {
        if ($model && $relations) {
            if (in_array('platformMiniprogram', $relations)) {
                $model->platformMiniprogram = UserPlatform::getCacheByUserIdAndPlatform(UserPlatform::PLATFORM_MINIPROGRAM, $model->id);
            }
            if (in_array('bosss', $relations)) {
                $model->bosss = User::getCacheBossListByIdAndClubId($model->playw_report_club_id, $model->id);
            }
        }
    }
}
