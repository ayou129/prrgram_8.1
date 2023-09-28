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
use App\Service\Utils\Redis\PlaywReport\MCStrategy\MCStrategyAbstract;
use Hyperf\Redis\Redis;

class McUser extends MCStrategyAbstract
{
    public const ttl = 3600 * 24 * 2;

    public string $table = '';

    public function __construct(?Redis $redis)
    {
        $this->table = (new User())->getTable();
        parent::__construct($redis);
    }

    public function setByPhone($phone, $id)
    {
        $key = self::getByPhoneKey($phone);
        return $this->set($key, $id, ['ex' => self::ttl]);
    }

    public function getByPhone($phone)
    {
        $key = self::getByPhoneKey($phone);
        return $this->get($key);
    }

    public function delByPhone($phone)
    {
        $key = self::getByPhoneKey($phone);
        return $this->del($key);
    }

    public function getByPhoneKey($phone): string
    {
        return $this->prefix . $this->table . '|phone:' . $phone;
    }

    public function setBossListSortCreatedAtByClubId($club_id, $u_id, $scope, $boss_id)
    {
        $key = self::getBossListSortCreatedAtByClubIdKey($club_id, $u_id);
        return [$this->zAdd($key, $scope, $boss_id), $this->ttl($key, self::ttl)];
    }

    public function getBossListSortCreatedAtByClubIdPaginate($club_id, $u_id, $start, $end): array
    {
        $key = self::getBossListSortCreatedAtByClubIdKey($club_id, $u_id);
        return $this->getPaginate($key, $start, $end);
    }

    public function getBossListSortCreatedAtByClubIdAll($club_id, $u_id): array
    {
        $key = self::getBossListSortCreatedAtByClubIdKey($club_id, $u_id);
        return $this->zrange($key, 0, -1);
    }

    public function delBossListSortCreatedAtByClubId($club_id, $u_id, $id, ...$ids)
    {
        $key = self::getBossListSortCreatedAtByClubIdKey($club_id, $u_id);
        return $this->zRem($key, $id, ...$ids);
    }

    public function getBossListSortCreatedAtByClubIdKey($club_id, $u_id): string
    {
        return $this->prefix . $this->table . '|boss_list_sort_created_at|club_id:' . $club_id . '|u_id:' . $u_id;
    }

    public function setOrderListSortCreatedAtByClubId($club_id, $u_id, $scope, $order_id)
    {
        $key = self::getOrderListSortCreatedAtByClubIdKey($club_id, $u_id);
        return [$this->zAdd($key, $scope, $order_id), $this->ttl($key, self::ttl)];
    }

    public function getOrderListSortCreatedAtByClubIdPaginate($club_id, $u_id, $start, $end): array
    {
        $key = self::getOrderListSortCreatedAtByClubIdKey($club_id, $u_id);
        return $this->getPaginate($key, $start, $end);
    }

    public function delOrderListSortCreatedAtByClubId($club_id, $u_id, $order_id, ...$ids)
    {
        $key = self::getOrderListSortCreatedAtByClubIdKey($club_id, $u_id);
        return $this->zRem($key, $order_id, ...$ids);
    }

    public function getOrderListSortCreatedAtByClubIdKey($club_id, $u_id): string
    {
        return $this->prefix . $this->table . '|order_list_sort_created_at|club_id:' . $club_id . '|u_id:' . $u_id;
    }
}
