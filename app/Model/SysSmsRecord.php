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
     */
    protected ?string $table = 'sys_sms_record';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'platform_id', 'phone', 'content', 'send_ip', 'template', 'result_code', 'record_id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'result_code' => 'integer', 'record_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
