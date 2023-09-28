<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
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
     *
     * @var string
     */
    protected $table = 'sys_log';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'description', 'log_type', 'method', 'params', 'request_ip', 'time', 'username', 'address', 'browser', 'exception_detail', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'time' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
