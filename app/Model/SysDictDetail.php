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
 * @property int $dict_id
 * @property string $label
 * @property string $value
 * @property int $dict_sort
 * @property string $create_by
 * @property string $update_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property null|SysDict $dict
 */
class SysDictDetail extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sys_dict_detail';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'dict_id', 'label', 'value', 'dict_sort', 'create_by', 'update_by', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'dict_id' => 'integer', 'dict_sort' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function dict()
    {
        return $this->hasOne(SysDict::class, 'id', 'dict_id');
    }
}
