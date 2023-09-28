<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

/**
 * @property int $id
 * @property int $club_id
 * @property int $period_type
 * @property int $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property \Hyperf\Database\Model\Collection|PlaywReportClubOrderStairPointExcludeProject[] $excludeProjects
 * @property \Hyperf\Database\Model\Collection|PlaywReportClubOrderStairPointExcludeUser[] $excludeUsers
 * @property \Hyperf\Database\Model\Collection|PlaywReportClubOrderStairPointRule[] $rule
 */
class PlaywReportClubOrderStairPoint extends BaseModel
{
    public const STATUS_DEFAULT = 0;

    public const STATUS_YES = 1;

    public const PERIOD_TYPE_WEEK = 1;

    public const PERIOD_TYPE_MONTH = 2;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'playw_report_club_order_stair_point';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'club_id', 'period_type', 'status', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'club_id' => 'integer', 'period_type' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected $appends = ['status_text'];

    public static function getStatusArray(): array
    {
        return [self::STATUS_DEFAULT => '未启用', self::STATUS_YES => '启用'];
    }

    public static function getPeriodTypeArray(): array
    {
        return [self::PERIOD_TYPE_WEEK => '周', self::PERIOD_TYPE_MONTH => '月'];
    }

    public function rule()
    {
        return $this->hasMany(PlaywReportClubOrderStairPointRule::class, 'stair_point_id', 'id');
    }

    public function excludeUsers()
    {
        return $this->hasMany(PlaywReportClubOrderStairPointExcludeUser::class, 'stair_point_id', 'id');
    }

    public function excludeProjects()
    {
        return $this->hasMany(PlaywReportClubOrderStairPointExcludeProject::class, 'stair_point_id', 'id');
    }
}
