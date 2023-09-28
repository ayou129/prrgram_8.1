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
 * @property int $user_id
 * @property string $name
 * @property string $mobile_number
 * @property string $province
 * @property string $city
 * @property string $area
 * @property string $detail
 * @property int $is_default
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class UserAddress extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'user_address';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'user_id', 'name', 'mobile_number', 'province', 'city', 'area', 'detail', 'is_default', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'user_id' => 'integer', 'is_default' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
