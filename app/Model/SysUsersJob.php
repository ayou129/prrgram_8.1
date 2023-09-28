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
 * @property int $user_id
 * @property int $job_id
 */
class SysUsersJob extends Model
{
    public bool $incrementing = false;

    public bool $timestamps = false;

    protected string $primaryKey = 'user_id';

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sys_users_jobs';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['user_id', 'job_id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['user_id' => 'integer', 'job_id' => 'integer'];
}
