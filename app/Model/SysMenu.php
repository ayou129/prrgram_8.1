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
 * @property int $pid
 * @property int $sub_count
 * @property int $type
 * @property string $title
 * @property string $name
 * @property string $component
 * @property int $menu_sort
 * @property string $icon
 * @property string $path
 * @property string $permission
 * @property string $create_by
 * @property string $update_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property string $deleted_at
 * @property bool|mixed $hidden
 * @property bool|mixed $is_frame
 * @property bool|mixed $cache
 */
class SysMenu extends BaseModel
{
    /**
     * The table associated with the model.
     */
    protected ?string $table = 'sys_menu';

    /**
     * The attributes that are mass assignable.
     */
    protected array $fillable = ['id', 'pid', 'sub_count', 'type', 'title', 'name', 'component', 'menu_sort', 'icon', 'path', 'is_frame', 'cache', 'hidden', 'permission', 'create_by', 'update_by', 'created_at', 'updated_at', 'deleted_at'];

    /**
     * The attributes that should be cast to native types.
     */
    protected array $casts = ['id' => 'integer', 'pid' => 'integer', 'sub_count' => 'integer', 'type' => 'integer', 'menu_sort' => 'integer', 'is_frame' => 'integer', 'cache' => 'integer', 'hidden' => 'integer', 'created_at' => 'datetime', 'updated_at' => 'datetime'];

    public function setHiddenAttribute($val)
    {
        $this->attributes['hidden'] = $this->getIntValueByInput($val);
    }

    public function getHiddenAttribute($val): bool
    {
        return $val === 1;
    }

    public function setIsFrameAttribute($val)
    {
        $this->attributes['is_frame'] = $this->getIntValueByInput($val);
    }

    public function getIsFrameAttribute($val): bool
    {
        return $val === 1;
    }

    public function setCacheAttribute($val)
    {
        $this->attributes['cache'] = $this->getIntValueByInput($val);
    }

    public function getCacheAttribute($val): bool
    {
        return $val === 1;
    }

    public static function addLabelField(array &$models)
    {
        foreach ($models as &$model) {
            $model['label'] = $model['title'];
        }
    }

    /**
     * 更新表内所有的sub_count数据.
     */
    public static function updateAllSubCount()
    {
        $countArray = [];
        $models = (new self())->get();
        if ($models->isEmpty()) {
            return;
        }
        $allData = $models->toArray();
        $allIds = [];
        foreach ($allData as $dept1) {
            $allIds[] = $dept1['id'];
            if ($dept1['pid']) {
                foreach ($allData as $dept2) {
                    if ($dept2['id'] === $dept1['pid']) {
                        if (! isset($countArray[$dept2['id']])) {
                            $countArray[$dept2['id']] = 1;
                        } else {
                            ++$countArray[$dept2['id']];
                        }
                    }
                }
            }
        }
        # 把没有处理的也设置成0
        foreach ($allIds as $id) {
            $has = false;
            foreach ($countArray as $exec_id => $item) {
                if ($exec_id === $id) {
                    $has = true;
                    break;
                }
            }
            if (! $has) {
                $countArray[$id] = 0;
            }
        }
        $saveUpdateData = [];
        foreach ($countArray as $id => $subCount) {
            $saveUpdateData[] = ['id' => $id, 'sub_count' => $subCount];
        }
        // var_dump($saveUpdateData);
        (new SysMenu())->updateBatch($saveUpdateData);
    }
}
