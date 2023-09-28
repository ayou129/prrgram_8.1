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

/**
 * @property int $id
 * @property int $club_id
 * @property int $stair_point_id
 * @property int $project_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property null|PlaywReportClubProject $project
 */
class PlaywReportClubOrderStairPointExcludeProject extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'playw_report_club_order_stair_point_exclude_project';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'club_id', 'stair_point_id', 'project_id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'club_id' => 'integer', 'stair_point_id' => 'integer', 'project_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function project()
    {
        return $this->hasOne(PlaywReportClubProject::class, 'id', 'project_id');
    }
}
