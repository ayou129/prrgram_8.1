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
 * @property string $description
 * @property string $create_by
 * @property string $update_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property null|\Hyperf\Database\Model\Collection|SysDictDetail[] $details
 */
class SysDict extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sys_dict';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'name', 'description', 'create_by', 'update_by', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function details()
    {
        return $this->hasMany(SysDictDetail::class, 'dict_id', 'id');
    }
}
