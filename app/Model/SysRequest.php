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
 * @property string $method
 * @property string $path
 * @property string $bodys
 * @property string $ip
 * @property int $u_id
 * @property string $exception_trace
 * @property string $exception_otherinfo
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property mixed $headers
 * @property mixed $user_agent
 * @property mixed $params
 * @property null|\Hyperf\Database\Model\Collection|SysRequestSql[] $sqls
 */
class SysRequest extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sys_request';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'method', 'path', 'headers', 'params', 'bodys', 'ip', 'user_agent', 'u_id', 'exception_trace', 'exception_otherinfo', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'u_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

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

    public function sqls()
    {
        return $this->hasMany(SysRequestSql::class, 'request_id', 'id');
    }
}
