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

namespace App\Service\Utils\Redis\PlaywReport\MCStrategy;

use Hyperf\Redis\Redis;
use RedisException;

abstract class MCStrategyAbstract
{
    protected string $prefix = 'mc|t_';

    protected Redis $redis;

    public function __construct(?Redis $redis)
    {
        $this->redis = $redis;
    }

    /**
     * @param mixed $method
     * @param mixed $args
     * @return mixed
     */
    public function __call($method, $args)
    {
        //        var_dump('__call', $method);
        // 记录日志
        $this->logRedisCommand($method, $args);

        // 执行Redis命令
        return $this->redis->{$method}(...$args);
    }

    public function setModel($id, array $data)
    {
        $key = self::getModelKey($id);
        return $this->hMset($key, $data, $this::ttl);
    }

    public function getModel($id)
    {
        $key = self::getModelKey($id);
        return $this->hGetAll($key);
    }

    public function getModels($ids)
    {
        $result = [];
        foreach ($ids as $id) {
            $result[] = self::getModel($id);
        }
        return $result;
    }

    public function delModel($id)
    {
        $key = self::getModelKey($id);
        return $this->del($key);
    }

    public function setSortCreatedAt($scope, $id)
    {
        $key = self::getSortCreatedAtKey();
        return [$this->zAdd($key, $scope, $id), $this->ttl($key, $this::ttl)];
    }

    public function getSortCreatedAtPaginate($start, $end): array
    {
        $key = self::getSortCreatedAtKey();
        return $this->getPaginate($key, $start, $end);
    }

    public function delSortCreatedAtZRemMembers($id, ...$ids)
    {
        $key = self::getSortCreatedAtKey();
        return $this->zRem($key, $id, ...$ids);
    }

    public function mGet($keys)
    {
        $result = $this->redis->mGet($keys);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result);
        return $result;
    }

    protected function hMset($key, array $data, $ttl)
    {
        $result1 = $this->redis->hMSet($key, $data);
        $result2 = $this->redis->expire($key, $ttl);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result1, $result2);
        return [$result1, $result2];
    }

    protected function hGetAll($key)
    {
        $result = $this->redis->hGetAll($key);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result);
        return $result;
    }

    protected function set($key, $value, array $options = [])
    {
        $result = $this->redis->set($key, $value, $options);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result);
        return $result;
    }

    protected function get($key)
    {
        $result = $this->redis->get($key);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result);
        return $result;
    }

    protected function ttl($key, $ttl)
    {
        $result = $this->redis->expire($key, $ttl);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result);
        return $result;
    }

    protected function del($key)
    {
        $result = $this->redis->del($key);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result);
        return $result;
    }

    /**
     * 获取hash指定成员值
     * @param mixed $key
     * @param mixed $hashKey
     * @return mixed
     */
    protected function hGet($key, $hashKey)
    {
        $result = $this->redis->hGet($key, $hashKey);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result);
        return $result;
    }

    protected function zDelete($key, $member1, ...$otherMembers)
    {
        $result = $this->redis->zDelete($key, $member1, ...$otherMembers);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result);
        return $result;
    }

    protected function zAdd($key, $score_or_options, ...$more_scores_and_mems)
    {
        $result = $this->redis->zAdd($key, $score_or_options, ...$more_scores_and_mems);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result);
        return $result;
    }

    protected function zRem($key, $member1, ...$otherMembers)
    {
        $result = $this->redis->zRem($key, $member1, ...$otherMembers);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result);
        return $result;
    }

    /**
     * 分页数据.
     * @param mixed $key
     * @param mixed $start
     * @param mixed $end
     * @return array|false|\Redis
     * @throws RedisException
     */
    protected function zRangeByScore($key, $start, $end)
    {
        $result = $this->redis->zRangeByScore($key, $start, $end);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result);
        return $result;
    }

    /**
     * 所有数据.
     * @param mixed $key
     * @param mixed $start
     * @param mixed $end
     * @return array|false|\Redis
     * @throws RedisException
     */
    protected function zrange($key, $start, $end)
    {
        $result = $this->redis->zrange($key, $start, $end);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result);
        return $result;
    }

    protected function sAdd($key, $value, ...$other_values)
    {
        $result = $this->redis->sAdd($key, $value, ...$other_values);
        $args = func_get_args();
        var_dump(__FUNCTION__, $args, $result);
        return $result;
    }

    //    ---------------- 下面作废 --------------

    protected function logRedisCommand($method, $args)
    {
        //        var_dump($args);
        //        if (is_array($args)) {
        //            $argsString = implode(', ', $args);
        //        }
        // 在这里记录Redis命令和参数，你可以将它们记录到日志文件或输出到控制台
        $logMessage = "Redis Command: {$method}";
        //        var_dump($logMessage, $args);
        // 你也可以将日志记录到文件或者其他适合的地方
        // file_put_contents('redis.log', $logMessage . PHP_EOL, FILE_APPEND);
    }

    /**
     * 表数据.
     * @param mixed $id
     */
    protected function getModelKey($id): string
    {
        $args = func_get_args();
        var_dump(__FUNCTION__, $args);
        return $this->prefix . $this->table . '|id:' . $id;
    }

    protected function getSortCreatedAtKey(): string
    {
        return $this->prefix . $this->table . '|list_sort_created_at';
    }

    protected function getPaginate($key, $page, $limit)
    {
        // 计算总条目数
        $totalItems = $this->redis->zCard($key);

        // 计算总页数
        $totalPages = ceil($totalItems / $limit);

        // 确保页数不超出范围
        $page = max(1, min($page, $totalPages));

        // 计算起始和结束索引
        $start = ($page - 1) * $limit;
        $end = $start + $limit - 1;

        // 获取当前页数据
        $members = $this->redis->zRevRange($key, $start, $end);

        // 构建分页结构
        return [
            'total' => $totalItems,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => $totalPages,
            'data' => $members,
        ];
    }
}
