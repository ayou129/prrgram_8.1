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

use App\Service\Utils\Redis\PlaywReport\McPlaywReportClub;
use App\Service\Utils\Redis\PlaywReport\ModelCacheTrait;
use Hyperf\Collection\Collection;
use Hyperf\Paginator\LengthAwarePaginator;

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
    use ModelCacheTrait;

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
        return [
            self::AUTO_APPLY_BOSS_CREATE_NO => '手动审批',
            self::AUTO_APPLY_BOSS_CREATE_YES => '自动审批',
        ];
    }

    public static function getAutoApplyClubJoinArray()
    {
        return [
            self::AUTO_APPLY_CLUB_JOIN_NO => '手动审批',
            self::AUTO_APPLY_CLUB_JOIN_YES => '自动审批',
        ];
    }

    public static function getAutoApplyClubLeaveArray()
    {
        return [
            self::AUTO_APPLY_CLUB_LEAVE_NO => '手动审批',
            self::AUTO_APPLY_CLUB_LEAVE_YES => '自动审批',
        ];
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

    /**
     * @param mixed $k
     * @return Collection|\Hyperf\Database\Model\Builder[]|\Hyperf\Database\Model\Collection
     */
    public static function getSortCreatedAtByGroupIdAll($k)
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywReportClub($redis);
        $cache = $mc->getSortCreatedAtByGroupIdAll($k);
        if ($cache) {
            $models = PlaywReportClubGroup::getCacheByIds($cache);
        } else {
            $models = (new PlaywReportClubGroup())->where('club_id', $k)
                ->get();
        }
        return $models;
    }

    public static function getBossListSortCreatedAtByClubIdAll($k)
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywReportClub($redis);
        $cache = $mc->getBossListSortCreatedAtByClubIdAll($k);
        //        var_dump($cache);
        if ($cache) {
            return PlaywReportPlaywClubBoss::getCacheByIds($cache);
        }
        return \Hyperf\Collection\collect();
    }

    public static function getUserListSortJoinAtByClubIdAll($k)
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywReportClub($redis);
        $cache = $mc->getSortJoinAtByUserIdAll($k);
        //        var_dump($cache);
        if ($cache) {
            return User::getCacheByIds($cache);
        }
        return \Hyperf\Collection\collect();
    }

    public static function getUserListSortJoinAtByClubIdPaginate($k, int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywReportClub($redis);
        $cache = $mc->getSortJoinAtByUserIdPaginate($k, $page, $limit);
        //        var_dump($cache);
        if ($cache) {
            $data = User::getCacheByIds($cache['data']);
            $models = new LengthAwarePaginator($data, $cache['total'], $cache['per_page'], $cache['current_page']);
        } else {
            $models = new LengthAwarePaginator([], 0, 1);
        }
        return $models;
    }

    public static function getBossListSortCreatedAtByClubIdPaginate($k, int $page = 1, int $limit = 10): LengthAwarePaginator
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywReportClub($redis);
        $cache = $mc->getBossListSortCreatedAtByClubIdPaginate($k, $page, $limit);
        //        var_dump($cache);
        if ($cache) {
            $data = PlaywReportPlaywClubBoss::getCacheByIds($cache['data']);
            $models = new LengthAwarePaginator($data, $cache['total'], $cache['per_page'], $cache['current_page']);
        } else {
            $models = new LengthAwarePaginator([], 0, 1);
        }
        return $models;
    }

    public static function getProjectListSortCreatedAtByClubIdAll($k)
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywReportClub($redis);
        $cache = $mc->getProjectListSortCreatedAtByClubIdAll($k);
        //        var_dump($cache);
        if ($cache) {
            return PlaywReportClubProject::getCacheByIds($cache);
        }
        return \Hyperf\Collection\collect();
    }

    public static function getProjectListSortIndexByClubIdAll($k, $range_method = 'asc')
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywReportClub($redis);
        $cache = $mc->getProjectListSortIndexByClubIdAll($k, $range_method);
        //        var_dump($cache);
        if ($cache) {
            return PlaywReportClubProject::getCacheByIds($cache);
        }
        return \Hyperf\Collection\collect();
    }
}
