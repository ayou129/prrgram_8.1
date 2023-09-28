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
     */
    protected ?string $table = 'playw_report_club_order_stair_point_rule';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'club_id', 'stair_point_id', 'type', 'from_amount', 'to_amount', 'point_method_fixed', 'point_method_ratio', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'club_id' => 'integer', 'stair_point_id' => 'integer', 'from_amount' => 'integer', 'to_amount' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected array $appends = ['type_text'];

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
