<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

/**
 * @property int $id
 * @property int $sub_count
 * @property string $name
 * @property int $dept_sort
 * @property string $create_by
 * @property string $update_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property bool $enabled
 * @property \Hyperf\Database\Model\Collection|SysRole[] $roles
 * @property mixed $pid
 */
class SysDept extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sys_dept';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'pid', 'sub_count', 'name', 'dept_sort', 'enabled', 'create_by', 'update_by', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'pid' => 'integer', 'sub_count' => 'integer', 'dept_sort' => 'integer', 'enabled' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

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
