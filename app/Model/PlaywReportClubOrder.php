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

use App\Service\Utils\Redis\PlaywReport\McPlaywClubOrder;

/**
 * @property int $id
 * @property int $club_id
 * @property int $u_id
 * @property int $z_u_id
 * @property int $club_group_method
 * @property int $club_group_id
 * @property string $club_group_name
 * @property int $project_id
 * @property string $project_name
 * @property int $club_boss_id
 * @property string $club_boss_wx_name
 * @property string $club_boss_wx_number
 * @property string $start_at
 * @property string $end_at
 * @property int $type
 * @property int $number
 * @property int $convert_number
 * @property int $pw_danjia_price
 * @property int $jiedan_price
 * @property int $jiedan_price_all
 * @property string $club_take_price
 * @property string $club_take_price_all
 * @property string $z_take_price
 * @property string $z_take_stair_point_discount_price
 * @property string $z_take_price_all
 * @property string $price_takes
 * @property string $price_takes_all
 * @property string $price
 * @property string $price_all
 * @property int $status
 * @property int $jq_status
 * @property int $fd_status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property null|PlaywReportPlaywClubBoss $boss
 */
class PlaywReportClubOrder extends BaseModel
{
    public const STATUS_DEFAULT = 0;

    // 已结账状态
    public const STATUS_FINISHED = 1;

    public const FD_STATUS_DEFAULT = 0;

    public const FD_STATUS_FINISHED = 1;

    public const JQ_STATUS_DEFAULT = 0;

    public const JQ_STATUS_YES = 1;

    public const CLUB_GROUP_METHOD_DEFAULT = 1;

    public const CLUB_GROUP_METHOD_OUT = 2;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'playw_report_club_order';

    protected array $appends = ['status_text', 'jq_status_text', 'fd_status_text', 'club_group_method_text'];

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'club_id', 'u_id', 'z_u_id', 'club_group_method', 'club_group_id', 'club_group_name', 'project_id', 'project_name', 'club_boss_id', 'club_boss_wx_name', 'club_boss_wx_number', 'start_at', 'end_at', 'type', 'number', 'convert_number', 'pw_danjia_price', 'jiedan_price', 'jiedan_price_all', 'club_take_price', 'club_take_price_all', 'z_take_price', 'z_take_stair_point_discount_price', 'z_take_price_all', 'price_takes', 'price_takes_all', 'price', 'price_all', 'status', 'jq_status', 'fd_status', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'club_id' => 'integer', 'u_id' => 'integer', 'z_u_id' => 'integer', 'club_group_method' => 'integer', 'club_group_id' => 'integer', 'project_id' => 'integer', 'club_boss_id' => 'integer', 'type' => 'integer', 'number' => 'integer', 'convert_number' => 'integer', 'pw_danjia_price' => 'integer', 'jiedan_price' => 'integer', 'jiedan_price_all' => 'integer', 'status' => 'integer', 'jq_status' => 'integer', 'fd_status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public static function getStatusArray(): array
    {
        return [self::STATUS_DEFAULT => '未结账', self::STATUS_FINISHED => '已结账'];
    }

    public static function getClubGroupMethodArray(): array
    {
        return [self::CLUB_GROUP_METHOD_DEFAULT => '群内', self::CLUB_GROUP_METHOD_OUT => '群外'];
    }

    public static function getJqStatusArray(): array
    {
        return [self::JQ_STATUS_DEFAULT => '陪玩费用未结清', self::JQ_STATUS_YES => '陪玩费用已结清'];
    }

    public static function getFdStatusArray(): array
    {
        return [self::FD_STATUS_DEFAULT => '直属未返点', self::FD_STATUS_FINISHED => '直属已返点'];
    }

    public function boss()
    {
        return $this->hasOne(PlaywReportPlaywClubBoss::class, 'id', 'club_boss_id');
    }

    public static function getCacheById($k, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywClubOrder($redis);
        $cache = $mc->getModel($k);
        if ($cache) {
            $model = (new self())->newInstance($cache, true);
        } else {
            $model = (new self())->where('id', $k)->first();
        }
        if ($model) {
            if (in_array('user', $relations)) {
                $model->user = User::getCacheById($model->u_id);
            }
            if (in_array('zUser', $relations)) {
                $model->zUser = User::getCacheById($model->z_u_id);
            }
            if (in_array('project', $relations)) {
                $model->project = PlaywReportClubProject::getCacheById($model->project_id);
            }
            if (in_array('boss', $relations)) {
                $model->boss = PlaywReportPlaywClubBoss::getCacheById($model->club_boss_id);
            }
        }
        return $model ?? null;
    }
}
