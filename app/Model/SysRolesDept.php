<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

use Hyperf\DbConnection\Model\Model;

/**
 * @property int $role_id
 * @property int $dept_id
 */
class SysRolesDept extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'role_id';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sys_roles_depts';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['role_id', 'dept_id'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['role_id' => 'integer', 'dept_id' => 'integer'];
}
