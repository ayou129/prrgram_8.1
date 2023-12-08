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
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property null|\Hyperf\Database\Model\Collection|WuliuSailSchedule[] $sailSchedule
 */
class WuliuShipCompany extends BaseModel
{
    public const ZHONGGU = 1;

    public const ANTONG = 2;

    public const XINFENG = 3;

    public const ZHONGYUAN = 4;

    /**
     * The table associated with the model.
     */
    protected ?string $table = 'wuliu_ship_company';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'name', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public static function getShipCompanyArray()
    {
        return [self::ZHONGGU => '中谷', self::ANTONG => '安通', self::XINFENG => '信丰', self::ZHONGYUAN => '中远'];
    }

    public function sailSchedule()
    {
        return $this->hasMany(WuliuSailSchedule::class, 'ship_company_id', 'id');
    }

    /**
     * 通过名称获取ID，找不到则返回false.
     * @param mixed $modelsArray 模型数组
     * @param mixed $name 名称
     */
    public static function getIdByName($modelsArray, $name)
    {
        $result = false;
        foreach ($modelsArray as $key => $value) {
            if ($value['name'] === $name) {
                $result = $value['id'];
                break;
            }
        }
        return $result;
    }

    public static function getIdBySeaWaybillNumber(string $seaWaybill_number)
    {
        if (substr($seaWaybill_number, 0, 4) === 'PASU') {
            return WuliuShipCompany::ZHONGYUAN;
        }
        if (substr($seaWaybill_number, 0, 2) === 'ZG') {
            return WuliuShipCompany::ZHONGGU;
        }
        if (substr($seaWaybill_number, 0, 1) === 'A') {
            return WuliuShipCompany::ANTONG;
        }
        return null;
    }

    public static function getIdByFullName(string $full_name)
    {
        $shipCompanyArray = self::getShipCompanyArray();
        foreach ($shipCompanyArray as $id => $name) {
            if (strpos($full_name, $name) !== false) {
                return $id;
            }
        }
        return false;
    }
}
