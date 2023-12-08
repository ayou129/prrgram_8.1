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
 */
class WuliuPartner extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'wuliu_partner';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'name', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    /**
     * 通过名称获取ID，找不到则返回false.
     * @param mixed $modelsArray 模型数组
     * @param mixed $name 名称
     */
    public static function getIdByName($modelsArray, $name)
    {
        $result = false;
        foreach ($modelsArray as $key => $value) {
            if (strpos($value['name'], $name) !== false) {
                // if ($value['name'] === $name) {
                $result = $value['id'];
                break;
            }
        }
        return $result;
    }
}
