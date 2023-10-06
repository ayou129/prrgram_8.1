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

use App\Model\PlaywReportClub;
use App\Service\Utils\Redis\PlaywReport\MCStrategy\MCStrategyAbstract;
use Hyperf\Redis\Redis;

class McPlaywReportClub extends MCStrategyAbstract
{
    public const ttl = 3600 * 24 * 2;

    public string $table = '';

    public function __construct(?Redis $redis)
    {
        $this->table = (new PlaywReportClub())->getTable();
        parent::__construct($redis);
    }

    public function setSortJoinAtByUserId($club_id, $scope, $id)
    {
        $key = self::getSortJoinAtByUserIdKey($club_id);
        return [
            $this->zAdd($key, $scope, $id),
            $this->ttl($key, self::ttl),
        ];
    }

    public function getSortJoinAtByUserIdAll($club_id): array
    {
        $key = self::getSortJoinAtByUserIdKey($club_id);
        return $this->zrange($key, 0, -1);
    }

    public function getSortJoinAtByUserIdPaginate($club_id, $start, $end)
    {
        $key = self::getSortJoinAtByUserIdKey($club_id);
        return $this->getPaginate($key, $start, $end);
    }

    public function delSortJoinAtByUserIdZRemMembers($club_id, $id, ...$ids)
    {
        $key = self::getSortJoinAtByUserIdKey($club_id);
        return $this->zRem($key, $id, ...$ids);
    }

    /**
     * 某个俱乐部的所有陪玩 根据加入时间排序.
     * @param mixed $id
     */
    public function getSortJoinAtByUserIdKey($id): string
    {
        return $this->prefix . $this->table . '|user_list_sort_join_at|id:' . $id;
    }

    public function setBossListSortCreatedAtByClubId($club_id, $scope, $id)
    {
        $key = self::getBossListSortCreatedAtByClubIdKey($club_id);
        return [
            $this->zAdd($key, $scope, $id),
            $this->ttl($key, self::ttl),
        ];
    }

    public function getBossListSortCreatedAtByClubIdPaginate($club_id, $start, $end): array
    {
        $key = self::getBossListSortCreatedAtByClubIdKey($club_id);
        return $this->getPaginate($key, $start, $end);
    }

    public function getBossListSortCreatedAtByClubIdAll($club_id): array
    {
        $key = self::getBossListSortCreatedAtByClubIdKey($club_id);
        return $this->zrange($key, 0, -1);
    }

    public function delBossListSortCreatedAtByClubIdPaginateZRemMembers($club_id, $id, ...$ids)
    {
        $key = self::getBossListSortCreatedAtByClubIdKey($club_id);
        return $this->zRem($key, $id, ...$ids);
    }

    /**
     * 某个俱乐部的所有老板 根据创建时间排序.
     * @param mixed $id
     */
    public function getBossListSortCreatedAtByClubIdKey($id): string
    {
        return $this->prefix . $this->table . '|boss_list_sort_created_at|id:' . $id;
    }

    public function setOrderListSortCreatedAtByClubId($club_id, $scope, $id)
    {
        $key = self::getOrderListSortCreatedAtByClubIdKey($club_id);
        return [
            $this->zAdd($key, $scope, $id),
            $this->ttl($key, self::ttl),
        ];
    }

    public function getOrderListSortCreatedAtByClubIdPaginate($club_id, $start, $end): array
    {
        $key = self::getOrderListSortCreatedAtByClubIdKey($club_id);
        return $this->getPaginate($key, $start, $end);
    }

    public function getOrderListSortCreatedAtByClubIdAll($club_id): array
    {
        $key = self::getOrderListSortCreatedAtByClubIdKey($club_id);
        return $this->zrange($key, 0, -1);
    }

    public function delOrderListSortCreatedAtByClubIdZRemMembers($club_id, $id, ...$ids)
    {
        $key = self::getOrderListSortCreatedAtByClubIdKey($club_id);
        return $this->zRem($key, $id, ...$ids);
    }

    /**
     * 某个俱乐部的所有老板 根据创建时间排序.
     * @param mixed $id
     */
    public function getOrderListSortCreatedAtByClubIdKey($id): string
    {
        return $this->prefix . $this->table . '|order_list_sort_created_at|id:' . $id;
    }

    public function setProjectListSortCreatedAtByClubId($club_id, $scope, $id)
    {
        $key = self::getProjectListSortCreatedAtByClubIdKey($club_id);
        return [
            $this->zAdd($key, $scope, $id),
            $this->ttl($key, self::ttl),
        ];
    }

    public function getProjectListSortCreatedAtByClubIdPaginate($club_id, $start, $end): array
    {
        $key = self::getProjectListSortCreatedAtByClubIdKey($club_id);
        return $this->getPaginate($key, $start, $end);
    }

    public function getProjectListSortCreatedAtByClubIdAll($club_id): array
    {
        $key = self::getProjectListSortCreatedAtByClubIdKey($club_id);
        return $this->zrange($key, 0, -1);
    }

    public function delProjectListSortCreatedAtByClubIdZRemMembers($club_id, $id, ...$ids)
    {
        $key = self::getProjectListSortCreatedAtByClubIdKey($club_id);
        return $this->zRem($key, $id, ...$ids);
    }

    /**
     * 某个俱乐部的所有老板 根据创建时间排序.
     * @param mixed $id
     */
    public function getProjectListSortCreatedAtByClubIdKey($id): string
    {
        return $this->prefix . $this->table . '|project_list_sort_created_at|id:' . $id;
    }

    public function setProjectListSortIndexByClubId($club_id, $scope, $id)
    {
        $key = self::getProjectListSortIndexByClubIdKey($club_id);
        return [
            $this->zAdd($key, $scope, $id),
            $this->ttl($key, self::ttl),
        ];
    }

    public function getProjectListSortIndexByClubIdPaginate($club_id, $start, $end): array
    {
        $key = self::getProjectListSortIndexByClubIdKey($club_id);
        return $this->getPaginate($key, $start, $end);
    }

    public function getProjectListSortIndexByClubIdAll($club_id, $range_method = 'asc'): array
    {
        $key = self::getProjectListSortIndexByClubIdKey($club_id);
        if ($range_method === 'asc') {
            return $this->zRevRangeByScore($key, 0, -1);
        }
        return $this->zRangeByScore($key, 0, -1);
    }

    public function delProjectListSortIndexByClubIdZRemMembers($club_id, $id, ...$ids)
    {
        $key = self::getProjectListSortIndexByClubIdKey($club_id);
        return $this->zRem($key, $id, ...$ids);
    }

    /**
     * 某个俱乐部的所有老板 根据创建时间排序.
     * @param mixed $id
     */
    public function getProjectListSortIndexByClubIdKey($id): string
    {
        return $this->prefix . $this->table . '|project_list_sort_index|id:' . $id;
    }

    public function setSortCreatedAtByGroupId($club_id, $scope, $id)
    {
        $key = self::getSortCreatedAtByGroupIdKey($club_id);
        return [
            $this->zAdd($key, $scope, $id),
            $this->ttl($key, self::ttl),
        ];
    }

    public function getSortCreatedAtByGroupIdAll($club_id): array
    {
        $key = self::getSortCreatedAtByGroupIdKey($club_id);
        return $this->zrange($key, 0, -1);
    }

    public function getSortCreatedAtByGroupIdPaginate($club_id, $start, $end): array
    {
        $key = self::getSortCreatedAtByGroupIdKey($club_id);
        return $this->getPaginate($key, $start, $end);
    }

    public function delSortCreatedAtByGroupIdZRemMembers($club_id, $id, ...$ids)
    {
        $key = self::getSortCreatedAtByGroupIdKey($club_id);
        return $this->zRem($key, $id, ...$ids);
    }

    /**
     * 某个俱乐部的所有老板 根据创建时间排序.
     * @param mixed $id
     */
    public function getSortCreatedAtByGroupIdKey($id): string
    {
        return $this->prefix . $this->table . '|group_list_sort_created_at|id:' . $id;
    }
}
