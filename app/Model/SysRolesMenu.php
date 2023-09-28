<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

use Hyperf\Database\Model\Model;

/**
 * @property int $menu_id
 * @property int $role_id
 */
class SysRolesMenu extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'menu_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sys_roles_menus';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['menu_id', 'role_id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['menu_id' => 'integer', 'role_id' => 'integer'];
}
