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

use App\Constant\ServiceCode;
use App\Exception\ServiceException;
use App\Service\Utils\Redis\PlaywReport\McPlaywReportClubProject;
use App\Service\Utils\Redis\PlaywReport\ModelCacheTrait;

/**
 * @property int $id
 * @property int $club_id
 * @property string $name
 * @property int $type
 * @property int $price_method
 * @property string $price_method_fixed
 * @property int $price_method_double
 * @property int $club_take_method
 * @property string $club_take_method_fixed
 * @property string $club_take_method_ratio
 * @property int $z_take_method
 * @property string $z_take_method_fixed
 * @property string $z_take_method_ratio
 * @property int $convert
 * @property int $convert_number
 * @property int $index
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 */
class PlaywReportClubProject extends BaseModel
{
    use ModelCacheTrait;

    public const TYPE_DEFAULT = 1;

    public const TYPE_GIFT = 2;

    public const PRICE_METHOD_PLAYW = 0;

    public const PRICE_METHOD_FIXED = 1;

    public const PRICE_METHOD_DOUBLE = 2;

    public const CLUB_TAKE_METHOD_FIXED = 1;

    public const CLUB_TAKE_METHOD_RATIO = 2;

    public const Z_TAKE_METHOD_FIXED = 1;

    public const Z_TAKE_METHOD_RATIO = 2;

    public const CONVERT_DEFAULT = 0;

    public const CONVERT_YES = 1;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'playw_report_club_project';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'club_id', 'name', 'type', 'price_method', 'price_method_fixed', 'price_method_double', 'club_take_method', 'club_take_method_fixed', 'club_take_method_ratio', 'z_take_method', 'z_take_method_fixed', 'z_take_method_ratio', 'convert', 'convert_number', 'index', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'club_id' => 'integer', 'type' => 'integer', 'price_method' => 'integer', 'price_method_double' => 'integer', 'club_take_method' => 'integer', 'z_take_method' => 'integer', 'convert' => 'integer', 'convert_number' => 'integer', 'index' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    protected array $appends = ['type_text', 'price_method_text', 'club_take_method_text', 'z_take_method_text', 'convert_text'];

    public static function getTypeArray()
    {
        return [self::TYPE_DEFAULT => '游戏单', self::TYPE_GIFT => '礼物单'];
    }

    public static function getPriceMethodArray()
    {
        return [self::PRICE_METHOD_PLAYW => '陪玩单价', self::PRICE_METHOD_FIXED => '固定金额', self::PRICE_METHOD_DOUBLE => '加倍单价(倍数*陪玩单价)'];
    }

    public static function getClubTakeMethodArray()
    {
        return [self::CLUB_TAKE_METHOD_FIXED => '固定抽成金额', self::CLUB_TAKE_METHOD_RATIO => '抽成比例'];
    }

    public static function getZTakeMethodArray()
    {
        return [self::Z_TAKE_METHOD_FIXED => '固定抽成金额', self::Z_TAKE_METHOD_RATIO => '抽成比例'];
    }

    public static function getConvertArray()
    {
        return [self::CONVERT_DEFAULT => '否', self::CONVERT_YES => '是'];
    }

    public static function checkModelField($model)
    {
        if ($model->price_method == self::PRICE_METHOD_FIXED && (! $model->price_method_fixed && ! is_numeric($model->price_method_fixed))) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '请填写固定金额');
        }
        if ($model->price_method == self::PRICE_METHOD_DOUBLE && (! $model->price_method_double && ! is_numeric($model->price_method_double))) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '请填写加倍单价');
        }
        if ($model->club_take_method == self::CLUB_TAKE_METHOD_FIXED && (! $model->club_take_method_fixed && ! is_numeric($model->club_take_method_fixed))) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '请填写固定抽成金额');
        }
        if ($model->club_take_method == self::CLUB_TAKE_METHOD_RATIO && (! $model->club_take_method_ratio && ! is_numeric($model->club_take_method_ratio))) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '请填写俱乐部抽成比例');
        }
        if ($model->z_take_method == self::Z_TAKE_METHOD_FIXED && (! $model->z_take_method_fixed && ! is_numeric($model->z_take_method_fixed))) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '请填写直属固定返点金额');
        }
        if ($model->z_take_method == self::Z_TAKE_METHOD_RATIO && (! $model->z_take_method_ratio && ! is_numeric($model->z_take_method_ratio))) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '请填写直属返点比例');
        }
        if ($model->convert == self::CONVERT_YES && (! $model->convert_number && ! is_numeric($model->convert_number))) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '请填写折单数量');
        }
        return true;
    }

    public static function getCacheById($k, $relations = [])
    {
        $redis = make(\Hyperf\Redis\Redis::class);
        $mc = new McPlaywReportClubProject($redis);
        $cache = $mc->getModel($k);
        if ($cache) {
            $model = (new self())->newInstance($cache, true);
        } else {
            $model = (new self())->where('id', $k)->first();
        }
        if ($model) {
        }
        return $model ?? null;
    }
}
