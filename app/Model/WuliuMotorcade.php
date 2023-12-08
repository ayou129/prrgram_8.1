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
 * @property string $name
 * @property int $type
 * @property string $base_salary
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class WuliuMotorcade extends BaseModel
{
    public const TYPE_DEDAULT = 0;

    public const TYPE_SELF = 1;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'wuliu_motorcade';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'name', 'type', 'base_salary', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'type' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected array $appends = ['type_text'];

    public static function getTypeArray()
    {
        return [self::TYPE_DEDAULT => '-', self::TYPE_SELF => '自己车'];
    }
}
