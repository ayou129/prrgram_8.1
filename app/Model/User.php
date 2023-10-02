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

namespace App\Model;

use App\Service\Utils\Redis\PlaywReport\McUser;
use App\Service\Utils\Redis\PlaywReport\ModelCacheTrait;
use Hyperf\Collection\Collection;
use Hyperf\Paginator\LengthAwarePaginator;

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
 * @property mixed $birthday
 * @property mixed $social_id
 * @property null|UserPlatform $platform
 * @property null|\Hyperf\Database\Model\Collection|UserPlatform[] $platforms
 * @property null|\Hyperf\Database\Model\Collection|PlaywReportPlaywClubBoss[] $boss
 * @property null|PlaywReportClub $club
 * @property null|PlaywReportApply $clubJoinApply
 * @property null|PlaywReportApply $clubLeaveApply
 */
class User extends BaseModel
{
    use ModelCacheTrait;

    public const PLAYW_REPORT_CLUB_ADMIN_DEFAULT = 0;

    public const PLAYW_REPORT_CLUB_ADMIN_YES = 1;

    public ?UserPlatform $platformMiniprogram;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'user';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'phone', 'wx_unionid', 'password', 'real_name', 'nickname', 'avatar_image_id', 'gender', 'birthday', 'constellation', 'city', 'province', 'country', 'status', 'playw_report_playwname', 'playw_report_club_jiedan_price', 'playw_report_club_id', 'playw_report_club_join_at', 'playw_report_club_admin', 'social_id', 'social_dazzle_nickname', 'social_signature', 'social_charm_value', 'social_magnate_value', 'social_noble_value', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'gender' => 'integer', 'status' => 'integer', 'playw_report_club_jiedan_price' => 'integer', 'playw_report_club_id' => 'integer', 'playw_report_club_admin' => 'integer', 'social_id' => 'integer', 'social_charm_value' => 'integer', 'social_magnate_value' => 'integer', 'social_noble_value' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected array $hidden = ['password'];

    protected array $appends = [
    ];

    public static function getPlaywReportClubAdminArray()
    {
        return [
            self::PLAYW_REPORT_CLUB_ADMIN_DEFAULT => '否',
            self::PLAYW_REPORT_CLUB_ADMIN_YES => '是',
        ];
    }

    public function setBirthdayAttribute($v)
    {
        $this->attributes['birthday'] = $v === '' ? null : $v;
    }

    public function setSocialIdAttribute($v)
    {
        $this->attributes['social_id'] = $v === '' ? null : $v;
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
        return $this->hasOne(PlaywReportApply::class, 'u_id', 'id')
            ->where('status', PlaywReportApply::STATUS_DEFAULT)
            ->where('type', PlaywReportApply::TYPE_CLUB_LEAVE);
    }

    public function clubJoinApply()
    {
        return $this->hasOne(PlaywReportApply::class, 'u_id', 'id')
            ->where('status', PlaywReportApply::STATUS_DEFAULT)
            ->where('type', PlaywReportApply::TYPE_CLUB_JOIN)
            ->with('club');
    }

    /**
     * @param mixed $k
     * @param mixed $relations
     * @return null|\Hyperf\Database\Model\Builder|\Hyperf\Database\Model\Model|object|User
     */
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
            $model = (new self())->where('phone', $k)
                ->first();
        }
        self::addRelations($model, $relations);
        return $model ?? null;
    }

    /**
     * @param mixed $k
     * @param mixed $k2
     * @param mixed $relations
     * @return LengthAwarePaginator
     */
    public static function getBossListSortCreatedAtByClubIdPaginate(
        $k,
        $k2,
        $relations = [],
        int $page = 1,
        int $limit = 10
    ) {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McUser($redis);
        $cache = $mc->getBossListSortCreatedAtByClubIdPaginate($k, $k2, $page, $limit);
        //        var_dump($cache);
        if ($cache) {
            $data = PlaywReportPlaywClubBoss::getCacheByIds($cache['data']);
            $models = new LengthAwarePaginator($data, $cache['total'], $cache['per_page'], $cache['current_page']);
        } else {
            $models = new LengthAwarePaginator([]);
        }
        return $models;
    }

    /**
     * @param mixed $k
     * @param mixed $k2
     * @param mixed $relations
     * @return Collection|\Hyperf\Database\Model\Builder[]|\Hyperf\Database\Model\Collection
     */
    public static function getBossListSortCreatedAtByClubIdAll($k, $k2, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McUser($redis);
        $cache = $mc->getBossListSortCreatedAtByClubIdAll($k, $k2);
        if ($cache) {
            $models = PlaywReportPlaywClubBoss::getCacheByIds($cache);
        } else {
            $models = (new PlaywReportPlaywClubBoss())->where('u_id', $k)
                ->where('playw_report_club_id', $k2)
                ->get();
        }
        foreach ($models as $model) {
            self::addRelations($model, $relations);
        }
        return $models;
    }

    public static function addRelations(&$model, $relations = [])
    {
        if ($model && $relations) {
            if (in_array('platformMiniprogram', $relations)) {
                $model->platformMiniprogram = UserPlatform::getCacheByUserIdAndPlatform(UserPlatform::PLATFORM_MINIPROGRAM, $model->id);
            }
            if (in_array('bosss', $relations)) {
                $model->bosss = User::getBossListSortCreatedAtByClubIdAll($model->playw_report_club_id, $model->id);
            }
        }
    }

    public static function addAttrText(&$model)
    {
        $model->label = $model->playw_report_playwname ?: '未设置昵称';
        $model->onshow = true;
    }
}
