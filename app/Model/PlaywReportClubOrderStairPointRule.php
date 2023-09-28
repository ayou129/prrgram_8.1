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
 * @property string $type
 * @property int $from_amount
 * @property int $to_amount
 * @property string $point_method_fixed
 * @property string $point_method_ratio
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class PlaywReportClubOrderStairPointRule extends BaseModel
{
    public const TYPE_JIEDANLIANG = 1;

    public const TYPE_DIANDANLIANG = 2;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'playw_report_club_order_stair_point_rule';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'club_id', 'stair_point_id', 'type', 'from_amount', 'to_amount', 'point_method_fixed', 'point_method_ratio', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'club_id' => 'integer', 'stair_point_id' => 'integer', 'from_amount' => 'integer', 'to_amount' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected $appends = ['type_text'];

    public static function getTypeArray()
    {
        return [self::TYPE_JIEDANLIANG => '接单量', self::TYPE_DIANDANLIANG => '点单量'];
    }

    public function setPointMethodFixed($value)
    {
        $this->attributes['params'] = $value ?? 0.0;
    }

    public function setPointMethodRatio($value)
    {
        $this->attributes['params'] = $value ?? 0.0;
    }
}
