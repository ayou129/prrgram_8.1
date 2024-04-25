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
 * @property int $dept_id
 * @property int $role_id
 * @property string $username
 * @property string $password
 * @property string $token
 * @property string $token_expiretime
 * @property string $nick_name
 * @property string $phone
 * @property string $email
 * @property string $avatar_name
 * @property string $avatar_path
 * @property int $status
 * @property string $pwd_reset_time
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property bool|mixed $enabled
 * @property null|SysDept $dept
 * @property null|SysRole $role
 * @property null|\Hyperf\Database\Model\Collection|SysJob[] $jobs
 */
class SysUser extends BaseModel
{
    public const IS_ADMIN = 1;

    public const IS_NOT_ADMIN = 0;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sys_user';

    protected array $hidden = ['password'];

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'dept_id', 'role_id', 'username', 'password', 'token', 'token_expiretime', 'nick_name', 'phone', 'email', 'avatar_name', 'avatar_path', 'status', 'pwd_reset_time', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'dept_id' => 'integer', 'role_id' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    // public function authorities()
    // {
    //     return $this->hasOne(Sys::class, 'user_id', 'id');
    // }
    public function getEnabledAttribute($val): bool
    {
        return $val === 1;
    }

    public function setEnabledAttribute($val)
    {
        $this->attributes['enabled'] = $this->getIntValueByInput($val);
    }

    public function dept()
    {
        return $this->hasOne(SysDept::class, 'id', 'dept_id');
    }

    public function role()
    {
        return $this->hasOne(SysRole::class, 'id', 'role_id');
    }

    public function jobs()
    {
        return $this->belongsToMany(SysJob::class, 'sys_users_jobs', 'user_id', 'job_id', 'id', 'id');
    }
}
