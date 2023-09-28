<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service\Business;

class PaginationService
{
    public function paginateZSet($zsetName, $page, $limit)
    {
        $redis = make(Redis::class);

        // 计算总条目数
        $totalItems = $redis->zCard($zsetName);

        // 计算总页数
        $totalPages = ceil($totalItems / $limit);

        // 确保页数不超出范围
        $page = max(1, min($page, $totalPages));

        // 计算起始和结束索引
        $start = ($page - 1) * $limit;
        $end = $start + $limit - 1;

        // 获取当前页数据
        $members = $redis->zRevRange($zsetName, $start, $end);

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
