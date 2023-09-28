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
 * @property int $stair_point_id
 * @property int $u_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property User $user
 */
class PlaywReportClubOrderStairPointExcludeUser extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'playw_report_club_order_stair_point_exclude_user';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'club_id', 'stair_point_id', 'u_id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'club_id' => 'integer', 'stair_point_id' => 'integer', 'u_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'u_id');
    }
}
