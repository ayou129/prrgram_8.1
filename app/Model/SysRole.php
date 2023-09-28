<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

/**
 * @property int $id
 * @property string $name
 * @property int $level
 * @property string $description
 * @property string $data_scope
 * @property string $create_by
 * @property string $update_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property \Hyperf\Database\Model\Collection|SysDept[] $depts
 * @property \Hyperf\Database\Model\Collection|SysMenu[] $menus
 */
class SysRole extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sys_role';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'name', 'level', 'description', 'data_scope', 'create_by', 'update_by', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'level' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function menus()
    {
        return $this->belongsToMany(SysMenu::class, 'sys_roles_menus', 'role_id', 'menu_id', 'id', 'id');
    }

    public function depts()
    {
        return $this->belongsToMany(SysDept::class, 'sys_roles_depts', 'role_id', 'dept_id', 'id', 'id');
    }
}
