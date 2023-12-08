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
 * @property string $number
 * @property int $motorcade_id
 * @property int $driver_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class WuliuCar extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'wuliu_car';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'number', 'motorcade_id', 'driver_id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'motorcade_id' => 'integer', 'driver_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
