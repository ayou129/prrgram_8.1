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
 * @property int $request_id
 * @property string $sql
 * @property string $sql_exec_time
 * @property string $created_at
 * @property string $deleted_at
 */
class SysRequestSql extends BaseModel
{
    public bool $timestamps = false;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sys_request_sql';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'request_id', 'sql', 'sql_exec_time', 'created_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'request_id' => 'integer'];
}
