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
 * @property string $description
 * @property string $log_type
 * @property string $method
 * @property string $params
 * @property string $request_ip
 * @property int $time
 * @property string $username
 * @property string $address
 * @property string $browser
 * @property string $exception_detail
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class SysLog extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sys_log';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'description', 'log_type', 'method', 'params', 'request_ip', 'time', 'username', 'address', 'browser', 'exception_detail', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'time' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
