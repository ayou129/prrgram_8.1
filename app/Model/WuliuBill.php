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
 * @property string $title
 * @property int $type
 * @property string $total_amount
 * @property int $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property mixed $type_text
 * @property mixed $status_text
 */
class WuliuBill extends BaseModel
{
    public const TYPE_SHIP_COMPANY = 1;

    public const TYPE_MOTORCADE = 2;

    public const TYPE_PARTNER = 3;

    public const TYPE_SELF = 4;

    public const STATUS_DEFAULT = 0;

    public const STATUS_CONFIRMED = 1;

    // public const STATUS_INVOICED = 5;
    public const STATUS_PAID = 10;

    public const TYPE_RECEIVED = 10;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'wuliu_bill';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'title', 'type', 'total_amount', 'status', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'type' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected array $appends = ['type_text', 'status_text'];

    public function getTypeTextAttribute()
    {
        return self::getTypeArray()[$this->attributes['type']];
    }

    public static function getTypeArray()
    {
        return [self::TYPE_SHIP_COMPANY => '船公司账单', self::TYPE_MOTORCADE => '车队账单', self::TYPE_PARTNER => '合作公司账单', self::TYPE_SELF => '自己车'];
    }

    public function getStatusTextAttribute()
    {
        return self::getStatusArray()[$this->attributes['status']];
    }

    public static function getStatusArray()
    {
        // self::STATUS_INVOICED => '已开具发票',
        return [self::STATUS_DEFAULT => '-', self::STATUS_CONFIRMED => '已确认', self::STATUS_PAID => '已付'];
    }
}
