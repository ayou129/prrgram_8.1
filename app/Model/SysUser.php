<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

/**
 * @property int $id
 * @property int $dept_id
 * @property string $username
 * @property string $password
 * @property string $token
 * @property string $token_expiretime
 * @property string $nick_name
 * @property string $gender
 * @property string $phone
 * @property string $email
 * @property string $avatar_name
 * @property string $avatar_path
 * @property int $is_admin
 * @property string $create_by
 * @property string $update_by
 * @property string $pwd_reset_time
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property SysDept $dept
 * @property bool $enabled
 * @property \Hyperf\Database\Model\Collection|SysJob[] $jobs
 * @property \Hyperf\Database\Model\Collection|SysRole[] $roles
 */
class SysUser extends BaseModel
{
    public const IS_ADMIN = 1;

    public const IS_NOT_ADMIN = 0;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sys_user';

    protected $hidden = ['password'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'dept_id', 'username', 'password', 'token', 'token_expiretime', 'nick_name', 'gender', 'phone', 'email', 'avatar_name', 'avatar_path', 'is_admin', 'enabled', 'create_by', 'update_by', 'pwd_reset_time', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'dept_id' => 'integer', 'is_admin' => 'integer', 'enabled' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

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

    public function roles()
    {
        return $this->belongsToMany(SysRole::class, 'sys_users_roles', 'user_id', 'role_id', 'id', 'id');
    }

    public function jobs()
    {
        return $this->belongsToMany(SysJob::class, 'sys_users_jobs', 'user_id', 'job_id', 'id', 'id');
    }
}
