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
 * @property int $job_sort
 * @property string $create_by
 * @property string $update_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property bool|mixed $enabled
 */
class SysJob extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sys_job';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'name', 'enabled', 'job_sort', 'create_by', 'update_by', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'enabled' => 'integer', 'job_sort' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function setEnabledAttribute($val)
    {
        $this->attributes['enabled'] = $this->getIntValueByInput($val);
    }

    public function getEnabledAttribute($val): bool
    {
        return $val === 1;
    }
}
