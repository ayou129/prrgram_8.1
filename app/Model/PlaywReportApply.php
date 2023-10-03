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

use App\Service\Utils\Redis\PlaywReport\ModelCacheTrait;

/**
 * @property int $id
 * @property int $u_id
 * @property int $club_id
 * @property int $type
 * @property int $exec_u_id
 * @property string $exec_at
 * @property int $status
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property mixed $params
 * @property null|User $user
 * @property null|PlaywReportClub $club
 */
class PlaywReportApply extends BaseModel
{
    use ModelCacheTrait;

    public const STATUS_DEFAULT = 0;

    public const STATUS_YES = 1;

    public const STATUS_CANCEL = 100;

    public const STATUS_NO = 101;

    public const TYPE_CLUB_JOIN = 1;

    public const TYPE_CLUB_LEAVE = 2;

    public const TYPE_BOSS_JOIN = 11;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'playw_report_apply';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'u_id', 'club_id', 'params', 'type', 'exec_u_id', 'exec_at', 'status', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'u_id' => 'integer', 'club_id' => 'integer', 'type' => 'integer', 'exec_u_id' => 'integer', 'status' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected array $appends = [];

    /**
     * 已存在的字段.
     * @param mixed $value
     */
    public function getParamsAttribute($value)
    {
        return $value ? json_decode($value, true) : '';
    }

    public function setParamsAttribute($value)
    {
        if (isset($value['token'])) {
            unset($value['token']);
        }
        $this->attributes['params'] = $value ? json_encode($value) : $value;
    }

    public static function getStatusArray()
    {
        return [
            self::STATUS_DEFAULT => '待处理',
            self::STATUS_YES => '已通过',
            self::STATUS_NO => '已拒绝',
            self::STATUS_CANCEL => '已取消',
        ];
    }

    public static function getTypeArray()
    {
        return [
            self::TYPE_CLUB_JOIN => '加入俱乐部审核',
            self::TYPE_CLUB_LEAVE => '退出俱乐部审核',
            self::TYPE_BOSS_JOIN => '报备老板审核',
        ];
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'u_id');
    }

    public function club()
    {
        return $this->hasOne(PlaywReportClub::class, 'id', 'club_id');
    }

    public static function addAttrText(&$model)
    {
        if ($model) {
            $model->status_text = self::getStatusArray()[$model->status];
        }
    }
}
