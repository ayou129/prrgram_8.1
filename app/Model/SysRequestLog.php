<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

/**
 * @property int $id
 * @property string $method
 * @property string $path
 * @property string $bodys
 * @property string $ip
 * @property int $user_id
 * @property string $exception_trace
 * @property string $exception_otherinfo
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property mixed $headers
 * @property mixed $params
 * @property mixed $user_agent
 */
class SysRequestLog extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sys_request_log';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'method', 'path', 'headers', 'params', 'bodys', 'ip', 'user_agent', 'user_id', 'exception_trace', 'exception_otherinfo', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'user_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function setHeadersAttribute($val)
    {
        $this->attributes['headers'] = $this->setJsonAttribute($val);
    }

    public function getHeadersAttribute($val)
    {
        return $this->getJsonAttribute($val);
    }

    public function setUserAgentAttribute($val)
    {
        $this->attributes['user_agent'] = $this->setJsonAttribute($val);
    }

    public function getUserAgentAttribute($val)
    {
        return $this->getJsonAttribute($val);
    }

    public function setParamsAttribute($val)
    {
        $this->attributes['params'] = $this->setJsonAttribute($val);
    }

    public function getParamsAttribute($val)
    {
        return $this->getJsonAttribute($val);
    }
}
