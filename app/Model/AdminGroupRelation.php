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
 * @property int $admin_id
 * @property int $group_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class AdminGroupRelation extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'admin_group_relation';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['admin_id', 'group_id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['admin_id' => 'integer', 'group_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
