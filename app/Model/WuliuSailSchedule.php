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
 * @property mixed $name_voyage
 * @property null|WuliuShipCompany $shipCompany
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

    protected array $appends = ['name_voyage'];

    public function getNameVoyageAttribute()
    {
        return $this->attributes['name'] . '-' . $this->attributes['voyage'];
    }

    public function shipCompany()
    {
        return $this->hasOne(WuliuShipCompany::class, 'id', 'ship_company_id');
    }

    /**
     * 通过 船公司ID+船名+航次 获取ID，找不到则返回false.
     * @param mixed $modelsArray
     * @param mixed $ship_company_id
     * @param mixed $name
     * @param mixed $voyage
     */
    public static function getIdByShipCompanyNameAndNameAndVoyage($modelsArray, $ship_company_id, $name, $voyage)
    {
        foreach ($modelsArray as $key => $value) {
            if ($value['ship_company_id'] == $ship_company_id && $value['name'] == $name && $value['voyage'] == $voyage) {
                return $value['id'];
                break;
            }
        }
        return false;
    }
}
