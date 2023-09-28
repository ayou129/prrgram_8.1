<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Model;

use App\Utils\Tools;
use Hyperf\Database\Model\SoftDeletes;
use Hyperf\DbConnection\Db;

// use Hyperf\Database\Model\Model; # 这里添加会出现严重的bug 模型的event无法执行
class BaseModel extends Model
{
    use SoftDeletes;

    public const CREATED_AT = 'created_at';

    public const UPDATED_AT = 'updated_at';

    public const DELETED_AT = 'deleted_at';

    public $timestamps = true;

    /**
     * 兼容类：获取器.
     * @param mixed $method
     * @param mixed $parameters
     */
    public function __call($method, $parameters)
    {
        if (strlen($method) > 16 && strpos($method, 'get') === 0 && strpos($method, 'TextAttribute') !== false) {
            $field = str_replace(['get', 'TextAttribute'], '', $method);
            if ($field) {
                $lowerField = Tools::strToUnderLineSpacing($field);
                // var_dump($field,$lowerField);
                $textFunctionName = 'get' . $field . 'Array';
                $array = $this::$textFunctionName();
                if (isset($this->attributes[$lowerField], $array[$this->attributes[$lowerField]])) {
                    // var_dump('ok', $method, $field, $array, $array[$this->attributes[$lowerField]]);
                    return $array[$this->attributes[$lowerField]];
                }
                return '';
            }
        }
        return parent::__call($method, $parameters);
    }

    public function setAttribute($key, $value)
    {
        if ($value === '') {
            $value = null;
        }
        parent::setAttribute($key, $value);
    }

    public function setJsonAttribute($val)
    {
        return json_encode($val);
    }

    public function getJsonAttribute($val)
    {
        return json_decode($val, true);
    }

    public function getHidden(): array
    {
        return array_merge($this->hidden, ['deleted_at']);
    }

    public static function addTreeFields(array &$models)
    {
        foreach ($models as &$model) {
            # has_children
            if (isset($model['sub_count']) && $model['sub_count'] > 0) {
                $model['has_children'] = true;
            } else {
                $model['has_children'] = false;
            }
            # leaf
            if (isset($model['sub_count']) && $model['sub_count'] <= 0) {
                $model['leaf'] = true;
            } else {
                $model['leaf'] = false;
            }
        }
    }

    /**
     * 批量更新.
     */
    public function updateBatch(array $multipleData)
    {
        try {
            $tableName = Db::getTablePrefix() . $this->getTable();
            $firstRow = current($multipleData);

            $updateColumn = array_keys($firstRow);
            // 默认以id为条件更新
            $referenceColumn = isset($firstRow['id']) ? 'id' : current($updateColumn);
            unset($updateColumn[0]);
            $updateSql = 'UPDATE ' . $tableName . ' SET ';
            $sets = [];
            $bindings = [];
            foreach ($updateColumn as $uColumn) {
                $setSql = '`' . $uColumn . '` = CASE ';
                foreach ($multipleData as $data) {
                    $setSql .= 'WHEN `' . $referenceColumn . '` = ? THEN ? ';
                    $bindings[] = $data[$referenceColumn];
                    $bindings[] = $data[$uColumn];
                }
                $setSql .= 'ELSE `' . $uColumn . '` END ';
                $sets[] = $setSql;
            }
            $updateSql .= implode(', ', $sets);
            $whereIn = collect($multipleData)->pluck($referenceColumn)->values()->all();
            $bindings = array_merge($bindings, $whereIn);
            $whereIn = rtrim(str_repeat('?,', count($whereIn)), ',');
            $updateSql = rtrim($updateSql, ', ') . ' WHERE `' . $referenceColumn . '` IN (' . $whereIn . ')';
            // var_dump($updateSql);
            return Db::update($updateSql, $bindings);
        } catch (\Exception $e) {
            return false;
        }
    }

    protected function getIntValueByInput($val): int
    {
        if (in_array($val, [
            'true',
            true,
            1,
            '1',
        ], true)) {
            return 1;
        }
        return 0;
    }

    protected function getIntOrNullValueByInput($val)
    {
        if (in_array($val, [
            '0',
            0,
            null,
            'false',
            false,
        ], true)) {
            return null;
        }
        return $val;
    }
}
