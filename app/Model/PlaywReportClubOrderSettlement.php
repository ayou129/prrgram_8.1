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
 * @property int $club_id
 * @property int $club_order_id
 * @property int $playw_id
 * @property int $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class PlaywReportClubOrderSettlement extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'playw_report_club_order_settlement';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'club_id', 'club_order_id', 'playw_id', 'status', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'club_id' => 'integer', 'club_order_id' => 'integer', 'playw_id' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
