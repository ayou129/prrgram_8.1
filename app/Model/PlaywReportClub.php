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

use App\Service\Utils\Redis\PlaywReport\McPlaywClub;
use Hyperf\Utils\Collection;

/**
 * @property int $id
 * @property int $u_id
 * @property int $leave_old_u_id
 * @property string $name
 * @property string $logo_url
 * @property int $auto_apply_boss_create
 * @property int $auto_apply_club_join
 * @property int $auto_apply_club_leave
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property null|\Hyperf\Database\Model\Collection|PlaywReportClubGroup[] $groups
 * @property null|\Hyperf\Database\Model\Collection|User[] $users
 * @property null|PlaywReportClubOrderStairPoint $stairPoint
 * @property null|\Hyperf\Database\Model\Collection|PlaywReportClubOrder[] $orders
 */
class PlaywReportClub extends BaseModel
{
    public const AUTO_APPLY_BOSS_CREATE_NO = 0;

    public const AUTO_APPLY_BOSS_CREATE_YES = 1;

    public const AUTO_APPLY_CLUB_JOIN_NO = 0;

    public const AUTO_APPLY_CLUB_JOIN_YES = 1;

    public const AUTO_APPLY_CLUB_LEAVE_NO = 0;

    public const AUTO_APPLY_CLUB_LEAVE_YES = 1;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'playw_report_club';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'u_id', 'leave_old_u_id', 'name', 'logo_url', 'auto_apply_boss_create', 'auto_apply_club_join', 'auto_apply_club_leave', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'u_id' => 'integer', 'leave_old_u_id' => 'integer', 'auto_apply_boss_create' => 'integer', 'auto_apply_club_join' => 'integer', 'auto_apply_club_leave' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public static function getAutoApplyBossArray()
    {
        return [self::AUTO_APPLY_BOSS_CREATE_NO => '手动审批', self::AUTO_APPLY_BOSS_CREATE_YES => '自动审批'];
    }

    public static function getAutoApplyClubJoinArray()
    {
        return [self::AUTO_APPLY_CLUB_JOIN_NO => '手动审批', self::AUTO_APPLY_CLUB_JOIN_YES => '自动审批'];
    }

    public static function getAutoApplyClubLeaveArray()
    {
        return [self::AUTO_APPLY_CLUB_LEAVE_NO => '手动审批', self::AUTO_APPLY_CLUB_LEAVE_YES => '自动审批'];
    }

    public function groups()
    {
        return $this->hasMany(PlaywReportClubGroup::class, 'club_id', 'id');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'playw_report_club_id', 'id');
    }

    public function stairPoint()
    {
        return $this->hasOne(PlaywReportClubOrderStairPoint::class, 'club_id', 'id');
    }

    public function orders()
    {
        return $this->hasMany(PlaywReportClubOrder::class, 'club_id', 'id');
    }

    public static function getCacheById($k, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywClub($redis);
        $cache = $mc->getModel($k);
        if ($cache) {
            $model = (new self())->newInstance($cache, true);
        } else {
            $model = (new self())->where('id', $k)->first();
        }
        if ($model) {
            if (in_array('groups', $relations)) {
                $model->groups = PlaywReportClub::getCacheGroupListById($model->id);
            }
            if (in_array('bosss', $relations)) {
                $model->bosss = PlaywReportClub::getCacheBossListById($model->id);
            }
        }
        return $model ?? null;
    }

    public static function getCacheGroupListById($k, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywClub($redis);
        $cache = $mc->getSortCreatedAtByGroupIdAll($k);
        var_dump($cache);
        if ($cache) {
            $models = PlaywReportClubGroup::getCacheByIds($cache);
        } else {
            $models = (new PlaywReportClubGroup())->where('club_id', $k)->get();
        }
        if ($models) {
        }
        return $models;
    }

    public static function getCacheBossListById($k, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywClub($redis);
        $cache = $mc->getBossListSortCreatedAtByClubIdAll($k);
        //        var_dump($cache);
        if ($cache) {
            $models = PlaywReportPlaywClubBoss::getCacheByIds($cache);
        } else {
            $models = new Collection([]);
        }
        if ($models) {
        }
        return $models;
    }
}
