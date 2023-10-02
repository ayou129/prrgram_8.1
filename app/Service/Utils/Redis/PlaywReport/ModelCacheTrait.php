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

namespace App\Service\Utils\Redis\PlaywReport;

use App\Model\User;
use App\Model\UserPlatform;
use Hyperf\Redis\Redis;

trait ModelCacheTrait
{
    public static function getCacheById($id)
    {
        $modelName = __CLASS__; // 获取当前模型的类名
        $redis = make(Redis::class);
        $mcClassName = 'App\Service\Utils\Redis\PlaywReport\Mc' . \Hyperf\Support\class_basename($modelName);
        $mc = new $mcClassName($redis);
        $cache = $mc->getModel($id);
        if ($cache) {
            $instance = new $modelName();
            $model = $instance->newInstance($cache, true);
        } else {
            $model = (new $modelName())->where('id', $id)
                ->first();
        }
        return $model ?? null;
    }

    public static function getCacheByIds(array $ids)
    {
        $modelName = __CLASS__; // 获取当前模型的类名
        $mcClassName = 'App\Service\Utils\Redis\PlaywReport\Mc' . \Hyperf\Support\class_basename($modelName);

        $redis = make(Redis::class);
        $mc = new $mcClassName($redis); // 使用 $mcClassName 实例化 $mc 类

        $cachedModels = [];
        $uncachedIds = [];

        // 将需要查询的 ID 分为已缓存和未缓存的部分
        foreach ($ids as $id) {
            $cache = $mc->getModel($id);
            if ($cache) {
                $cachedModels[$id] = (new $modelName())->newInstance($cache, true);
            } else {
                $uncachedIds[] = $id;
            }
        }

        // 如果有未缓存的 ID，从数据库中获取并缓存
        if (! empty($uncachedIds)) {
            $modelsFromDatabase = (new $modelName())->whereIn('id', $uncachedIds)
                ->get();
            foreach ($modelsFromDatabase as $model) {
                // 缓存新获取的模型数据
                $mc->setModel($model->id, $model->toArray());
                $cachedModels[$model->id] = $model;
            }
        }

        return \Hyperf\Collection\collect($cachedModels);
    }

    public static function addRelations(&$model, $relations = [])
    {
        if ($model && $relations) {
            if (in_array('platformMiniprogram', $relations)) {
                $model->platformMiniprogram = UserPlatform::getCacheByUserIdAndPlatform(UserPlatform::PLATFORM_MINIPROGRAM, $model->id);
            }
            if (in_array('user_bosss', $relations)) {
                $model->bosss = User::getBossListSortCreatedAtByClubIdAll($model->playw_report_club_id, $model->id);
            }
            if (in_array('user', $relations)) {
                $model->user = User::getCacheById($model->u_id);
            }
        }
    }

    public static function addAttrText(&$model)
    {
    }
}
