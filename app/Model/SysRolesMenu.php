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

use Hyperf\Database\Model\Model;

/**
 * @property int $menu_id
 * @property int $role_id
 */
class SysRolesMenu extends Model
{
    public bool $incrementing = false;

    public bool $timestamps = false;

    protected string $primaryKey = 'menu_id';

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sys_roles_menus';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['menu_id', 'role_id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['menu_id' => 'integer', 'role_id' => 'integer'];
}
