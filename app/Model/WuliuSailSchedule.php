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
 * @property string $name
 * @property string $voyage
 * @property string $arrival_date
 * @property int $ship_company_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class WuliuSailSchedule extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'wuliu_sail_schedule';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'name', 'voyage', 'arrival_date', 'ship_company_id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'ship_company_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
