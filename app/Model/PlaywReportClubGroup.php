<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

use App\Service\Utils\Redis\PlaywReport\McPlaywClubGroup;
use Hyperf\Utils\Collection;

/**
 * @property int $id
 * @property int $club_id
 * @property string $name
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class PlaywReportClubGroup extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'playw_report_club_group';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'club_id', 'name', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'club_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public static function getCacheById($k, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywClubGroup($redis);
        $cache = $mc->getModel($k);
        if ($cache) {
            $model = (new self())->newInstance($cache, true);
        } else {
            $model = (new self())->where('id', $k)->first();
        }
        if ($model) {
        }
        return $model ?? null;
    }

    public static function getCacheByIds($k, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywClubGroup($redis);
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
