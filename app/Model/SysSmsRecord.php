<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

/**
 * @property int $id
 * @property string $platform_id
 * @property string $phone
 * @property string $content
 * @property string $send_ip
 * @property string $template
 * @property int $result_code
 * @property int $record_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class SysSmsRecord extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'sys_sms_record';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['id', 'platform_id', 'phone', 'content', 'send_ip', 'template', 'result_code', 'record_id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = ['id' => 'integer', 'result_code' => 'integer', 'record_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
