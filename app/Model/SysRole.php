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
 * @property string $value
 * @property int $sort
 * @property string $remark
 * @property int $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property null|\Hyperf\Database\Model\Collection|SysMenu[] $menus
 * @property null|SysUser $user
 * @property null|\Hyperf\Database\Model\Collection|SysDept[] $depts
 */
class SysRole extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sys_role';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'name', 'value', 'sort', 'remark', 'status', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'sort' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function menus()
    {
        return $this->belongsToMany(SysMenu::class, 'sys_roles_menus', 'role_id', 'menu_id', 'id', 'id');
    }

    public function user()
    {
        return $this->hasOne(SysUser::class, 'role_id', 'id');
    }

    public function depts()
    {
        return $this->belongsToMany(SysDept::class, 'sys_roles_depts', 'role_id', 'dept_id', 'id', 'id');
    }
}
