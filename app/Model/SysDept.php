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
 * @property int $sub_count
 * @property string $name
 * @property int $dept_sort
 * @property int $status
 * @property string $remark
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property bool|mixed $enabled
 * @property mixed $pid
 * @property null|\Hyperf\Database\Model\Collection|SysRole[] $roles
 */
class SysDept extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sys_dept';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'pid', 'sub_count', 'name', 'dept_sort', 'status', 'remark', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'pid' => 'integer', 'sub_count' => 'integer', 'dept_sort' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function setEnabledAttribute($val)
    {
        $this->attributes['enabled'] = $this->getIntValueByInput($val);
    }

    public function getEnabledAttribute($val): bool
    {
        return $val === 1;
    }

    public function setPidAttribute($val)
    {
        $this->attributes['pid'] = $this->getIntOrNullValueByInput($val);
    }

    public function roles()
    {
        return $this->belongsToMany(SysRole::class, 'sys_roles_depts', 'dept_id', 'role_id', 'id', 'id');
    }

    public static function addLabelField(array &$models)
    {
        foreach ($models as &$model) {
            $model['label'] = $model['name'];
        }
    }
}
