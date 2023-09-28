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

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $role_id
 * @property int $dept_id
 */
class SysRolesDept extends Model
{
    public bool $incrementing = false;

    public bool $timestamps = false;

    protected string $primaryKey = 'role_id';

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sys_roles_depts';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['role_id', 'dept_id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['role_id' => 'integer', 'dept_id' => 'integer'];
}
