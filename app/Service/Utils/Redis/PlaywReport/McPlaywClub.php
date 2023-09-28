<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service\Utils\Redis\PlaywReport;

use App\Model\PlaywReportClub;
use App\Service\Utils\Redis\PlaywReport\MCStrategy\MCStrategyAbstract;
use Hyperf\Redis\Redis;

class McPlaywClub extends MCStrategyAbstract
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
        return [$this->zAdd($key, $scope, $id), $this->ttl($key, self::ttl)];
    }

    public function getSortJoinAtByUserIdPaginate($club_id, $start, $end)
    {
        $key = self::getSortJoinAtByUserIdKey($club_id);
        return $this->zRangeByScore($key, $start, $end);
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
        return [$this->zAdd($key, $scope, $id), $this->ttl($key, self::ttl)];
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
        return [$this->zAdd($key, $scope, $id), $this->ttl($key, self::ttl)];
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

    public function setSortCreatedAtByGroupId($club_id, $scope, $id)
    {
        $key = self::getSortCreatedAtByGroupIdKey($club_id);
        return [$this->zAdd($key, $scope, $id), $this->ttl($key, self::ttl)];
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
