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
 * @property int $playw_u_id
 * @property string $point_method_fixed
 * @property string $point_method_ratio
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property PlaywReportClubOrderStairPoint $stairPoint
 */
class PlaywReportClubOrderStairPointGenAssociation extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'playw_report_club_order_stair_point_gen_associations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'club_id', 'stair_point_id', 'playw_u_id', 'point_method_fixed', 'point_method_ratio', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'club_id' => 'integer', 'stair_point_id' => 'integer', 'playw_u_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function stairPoint()
    {
        return $this->hasOne(PlaywReportClubOrderStairPoint::class, 'id', 'stair_point_id');
    }
}
