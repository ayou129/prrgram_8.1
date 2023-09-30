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

use App\Service\Utils\Redis\PlaywReport\McPlaywClubBoss;
use Hyperf\Collection\Collection;

/**
 * @property int $id
 * @property int $u_id
 * @property int $club_id
 * @property int $group_id
 * @property string $wx_name
 * @property string $wx_number
 * @property string $img_url
 * @property string $join_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class PlaywReportPlaywClubBoss extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'playw_report_playw_club_boss';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'u_id', 'club_id', 'group_id', 'wx_name', 'wx_number', 'img_url', 'join_at', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'u_id' => 'integer', 'club_id' => 'integer', 'group_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public static function getCacheById($k, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywClubBoss($redis);
        $cache = $mc->getModel($k);
        if ($cache) {
            $model = (new self())->newInstance($cache, true);
        } else {
            $model = (new self())->where('id', $k)->first();
        }
        if ($model) {
            if (in_array('z', $relations)) {
                $model->z = User::getCacheById($model->u_id);
            }
            if (in_array('group', $relations)) {
                $model->group = PlaywReportClubGroup::getCacheById($model->group_id);
            }
        }
        return $model ?? null;
    }

    public static function getCacheByIds($k, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywClubBoss($redis);
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
        if ($models) {
        }

        return $models;
    }
}
