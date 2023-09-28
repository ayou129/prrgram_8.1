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
 * @property int $u_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class PlaywReportClubAdmin extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'playw_report_club_admin';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'u_id', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'u_id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];
}
