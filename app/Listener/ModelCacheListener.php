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

namespace App\Listener;

use App\Model\PlaywReportClub;
use App\Model\PlaywReportClubGroup;
use App\Model\PlaywReportClubOrder;
use App\Model\PlaywReportClubProject;
use App\Model\PlaywReportPlaywClubBoss;
use App\Model\User;
use App\Model\UserPlatform;
use App\Service\Utils\Redis\PlaywReport\McPlaywClub;
use App\Service\Utils\Redis\PlaywReport\McPlaywClubBoss;
use App\Service\Utils\Redis\PlaywReport\McPlaywClubGroup;
use App\Service\Utils\Redis\PlaywReport\McPlaywClubOrder;
use App\Service\Utils\Redis\PlaywReport\McPlaywClubProject;
use App\Service\Utils\Redis\PlaywReport\McUser;
use App\Service\Utils\Redis\PlaywReport\McUserPlatform;
use Hyperf\Database\Model\Events\Deleted;
use Hyperf\Database\Model\Events\Event;
use Hyperf\Database\Model\Events\Retrieved;
use Hyperf\Database\Model\Events\Saved;
use Hyperf\DbConnection\Db;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Redis\Redis;

#[Listener]
class ModelCacheListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            // //            QueryExecuted::class,
            Retrieved::class,
            Deleted::class,
            // //            Saving::class,
            Saved::class,
        ];
    }

    public function process(object $event): void
    {
        var_dump('in 模型Event');
        if (! $event instanceof Event) {
            return;
        }
        $model = $event->getModel();
        $dirtyArray = $model->getDirty();
        $originalArray = $model->getOriginal();
        //        var_dump($dirtyArray, $originalArray, $model->toArray());
        //        return;
        var_dump('----------- 模型Event ----------- method：' . $event->getMethod() . ' ----------- 表：' . $model->getTable() . ' -----------', $model->getDirty());
        $redis = make(Redis::class);
        $redis->pipeline();
        $mcUser = new McUser($redis);
        $mcUserPlatform = new McUserPlatform($redis);
        $mcPlaywClub = new McPlaywClub($redis);
        $mcPlaywClubGroup = new McPlaywClubGroup($redis);
        $mcPlaywClubProject = new McPlaywClubProject($redis);
        $mcPlaywClubBoss = new McPlaywClubBoss($redis);
        $mcPlaywClubOrder = new McPlaywClubOrder($redis);

        switch ($event->getMethod()) {
            case 'retrieved':
            case 'created':
            case 'saved':
                /*
                 * 查询：1.redis不存在、mysql可能存在 set
                 * 创建：1.redis不存在、mysql不存在 set
                 * 更新：1.redis不存在、redis存在(要删除) del set
                 * 删除：1.mysql存在，直接删redis del
                 */
                /*
                 * 造成脏数据的原因是：
                 *  1.直接删除mysql
                 *  2.mysql更新时没有及时更新redis
                 */
                //                var_dump($event->getMethod(), $model->getTable(), $originalArray, $dirtyArray);
                # 固定
                switch (true) {
                    case $model instanceof User:
                        if ($originalArray) {
                            # 有值，说明是save；没值，说明是create
                            // 保存后，需要删除原来数据的redis信息
                            $id = $originalArray['id'] ?? $model->id;
                            $phone = $originalArray['phone'] ?? $model->phone;
                            $playw_report_club_id = $originalArray['playw_report_club_id'] ?? $model->playw_report_club_id;
                            if (isset($originalArray['id']) && $originalArray['id'] != $model->id) {
                                $mcUser->delModel($id);
                            }
                            /*
                             * created_at不用删，因为是zset，赋值的时候会更新.
                             */
                            if (isset($originalArray['phone']) && $originalArray['phone'] != $model->phone) {
                                $mcUser->delByPhone($phone);
                            }
                            if (isset($originalArray['playw_report_club_id']) && $originalArray['playw_report_club_id'] != $model->playw_report_club_id) {
                                $mcPlaywClub->delSortJoinAtByUserIdZRemMembers($playw_report_club_id, $id);
                            }
                        }
                        $item = Db::table((new User())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::user($mcUser, $item, $mcPlaywClub);

                        break;
                    case $model instanceof UserPlatform:
                        if ($originalArray) {
                            # 有值，说明是save；没值，说明是create

                            $id = $originalArray['id'] ?? $model->id;
                            $wx_openid = $originalArray['wx_openid'] ?? $model->wx_openid;
                            $u_id = $originalArray['u_id'] ?? $model->u_id;
                            $platform = $originalArray['platform'] ?? $model->platform;
                            $login_token = $originalArray['login_token'] ?? $model->login_token;
                            if (isset($originalArray['id']) && $originalArray['id'] != $model->id) {
                                $mcUserPlatform->delModel($id);
                            }
                            /*
                             * created_at不用删，因为是zset，赋值的时候会更新.
                             */
                            if (isset($originalArray['login_token']) && $originalArray['login_token'] != $model->login_token) {
                                $mcUserPlatform->delByPlatformAndLoginToken($platform, $login_token);
                            }
                            if (isset($originalArray['u_id']) && $originalArray['u_id'] != $model->u_id) {
                                $mcUserPlatform->delByPlatformAndUserId($platform, $u_id);
                            }
                            if (isset($originalArray['wx_openid']) && $originalArray['wx_openid'] != $model->wx_openid) {
                                $mcUserPlatform->delByPlatformAndUserIdAndWxOpenid($platform, $u_id, $wx_openid);
                            }
                        }

                        $item = Db::table((new UserPlatform())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::userPlatform($mcUserPlatform, $item);

                        break;
                    case $model instanceof PlaywReportClub:
                        if ($originalArray) {
                            # 有值，说明是save；没值，说明是create
                            $id = $originalArray['id'] ?? $model->id;
                            if (isset($originalArray['id']) && $originalArray['id'] != $model->id) {
                                $mcPlaywClub->delModel($id);
                            }
                            /*
                             * created_at不用删，因为是zset，赋值的时候会更新.
                             */
                        }

                        $item = Db::table((new PlaywReportClub())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::club($mcPlaywClub, $item);

                        break;
                    case $model instanceof PlaywReportClubGroup:
                        if ($originalArray) {
                            # 有值，说明是save；没值，说明是create
                            // 保存后，需要删除原来数据的redis信息
                            $id = $originalArray['id'] ?? $model->id;
                            if (isset($originalArray['id']) && $originalArray['id'] != $model->id) {
                                $mcPlaywClubGroup->delModel($id);
                            }
                            /*
                             * created_at不用删，因为是zset，赋值的时候会更新.
                             */
                        }

                        $item = Db::table((new PlaywReportClubGroup())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::clubGroup($mcPlaywClubGroup, $item, $mcPlaywClub);

                        break;
                    case $model instanceof PlaywReportPlaywClubBoss:
                        if ($originalArray) {
                            # 有值，说明是save；没值，说明是create
                            // 保存后，需要删除原来数据的redis信息
                            $id = $originalArray['id'] ?? $model->id;
                            if (isset($originalArray['id']) && $originalArray['id'] != $model->id) {
                                $mcPlaywClubBoss->delModel($id);
                            }
                            /**
                             * created_at不用删，因为是zset，赋值的时候会更新.
                             */
                            $u_id = $originalArray['u_id'] ?? $model->u_id;
                            $club_id = $originalArray['club_id'] ?? $model->club_id;
                            if (isset($originalArray['u_id']) && $originalArray['u_id'] != $model->u_id) {
                                $mcUser->delBossListSortCreatedAtByClubId($club_id, $u_id, $id);
                            }
                            if (isset($originalArray['club_id']) && $originalArray['club_id'] != $model->club_id) {
                                $mcPlaywClub->delBossListSortCreatedAtByClubIdPaginateZRemMembers($club_id, $id);
                            }
                        }

                        $item = Db::table((new PlaywReportPlaywClubBoss())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::clubBoss($mcPlaywClubBoss, $item, $mcUser, $mcPlaywClub);

                        break;
                    case $model instanceof PlaywReportClubProject:
                        if ($originalArray) {
                            # 有值，说明是save；没值，说明是create
                            $id = $originalArray['id'] ?? $model->id;
                            if (isset($originalArray['id']) && $originalArray['id'] != $model->id) {
                                $mcPlaywClubProject->delModel($id);
                            }
                            /*
                             * created_at不用删，因为是zset，赋值的时候会更新.
                             */
                        }
                        $item = Db::table((new PlaywReportClubProject())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::clubProject($mcPlaywClubProject, $item);

                        break;
                    case $model instanceof PlaywReportClubOrder:
                        if ($originalArray) {
                            # 有值，说明是save；没值，说明是create
                            $id = $originalArray['id'] ?? $model->id;
                            if (isset($originalArray['id']) && $originalArray['id'] != $model->id) {
                                $mcPlaywClubOrder->delModel($id);
                            }
                            /**
                             * created_at不用删，因为是zset，赋值的时候会更新.
                             */
                            $u_id = $originalArray['u_id'] ?? $model->u_id;
                            $club_id = $originalArray['club_id'] ?? $model->club_id;
                            if (isset($originalArray['u_id']) && $originalArray['u_id'] != $model->u_id) {
                                $mcUser->delOrderListSortCreatedAtByClubId($club_id, $u_id, $id);
                            }
                            if (isset($originalArray['club_id']) && $originalArray['club_id'] != $model->club_id) {
                                $mcPlaywClub->delOrderListSortCreatedAtByClubIdZRemMembers($club_id, $id);
                            }
                        }

                        $item = Db::table((new PlaywReportClubOrder())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::clubOrder($mcPlaywClubOrder, $item, $mcUser, $mcPlaywClub);
                        break;
                    default:
                }
                break;
            case 'deleted':
                # 固定
                switch (true) {
                    case $model instanceof User:
                        $item = Db::table((new User())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::delUser($mcUser, $item, $mcPlaywClub);
                        break;
                    case $model instanceof UserPlatform:
                        $item = Db::table((new UserPlatform())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::delUserPlatform($mcUserPlatform, $item);
                        break;
                    case $model instanceof PlaywReportClub:
                        $item = Db::table((new PlaywReportClub())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::delClub($mcPlaywClub, $item);
                        break;
                    case $model instanceof PlaywReportClubGroup:
                        $item = Db::table((new PlaywReportClubGroup())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::delClubGroup($mcPlaywClubGroup, $item, $mcPlaywClub);
                        break;
                    case $model instanceof PlaywReportPlaywClubBoss:
                        $item = Db::table((new PlaywReportClubGroup())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::delClubBoss($mcPlaywClubBoss, $item, $mcUser, $mcPlaywClub);
                        break;
                    case $model instanceof PlaywReportClubProject:
                        $item = Db::table((new PlaywReportClub())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::delClubProject($mcPlaywClubProject, $item);
                        break;
                    case $model instanceof PlaywReportClubOrder:
                        $item = Db::table((new PlaywReportClubOrder())->getTable())->whereNull('deleted_at')->find($model->id);
                        self::delClubOrder($mcPlaywClubOrder, $item, $mcUser, $mcPlaywClub);
                        break;
                    default:
                }
                break;
            default:
        }
        $redis->exec();
    }

    public static function user(McUser $mcUser, $item, McPlaywClub $mcPlaywClub)
    {
        $mcUser->setModel($item->id, (array) $item);

        if ($timestamp = strtotime($item->created_at)) {
            $mcUser->setSortCreatedAt($timestamp, $item->id);
        }

        if ($item->phone) {
            $mcUser->setByPhone($item->phone, $item->id);
        }

        // 俱乐部 add 陪玩
        if ($item->playw_report_club_id && $join_at_timestamp = strtotime($item->playw_report_club_join_at)) {
            $mcPlaywClub->setSortJoinAtByUserId($item->playw_report_club_id, $join_at_timestamp, $item->id);
        }
    }

    public static function delUser(McUser $mcUser, $item, McPlaywClub $mcPlaywClub)
    {
        $mcUser->delModel($item->id);
        $mcUser->delSortCreatedAtZRemMembers($item->id);
        $mcUser->delByPhone($item->phone);

        # 俱乐部 remove 陪玩
        $mcPlaywClub->delSortJoinAtByUserIdZRemMembers($item->playw_report_club_id, $item->id);
    }

    public static function userPlatform(McUserPlatform $mcUserPlatform, $item)
    {
        $mcUserPlatform->setModel($item->id, (array) $item);

        if ($timestamp = strtotime($item->created_at)) {
            $mcUserPlatform->setSortCreatedAt($timestamp, $item->id);
        }

        if ($item->platform) {
            if ($item->login_token) {
                $mcUserPlatform->setByPlatformAndByLoginToken($item->platform, $item->login_token, $item->id);
            }
            if ($item->wx_openid) {
                $mcUserPlatform->setByPlatformAndUserIdAndWxOpenid($item->platform, $item->u_id, $item->wx_openid, $item->id);
            }
            if ($item->u_id) {
                $mcUserPlatform->setByPlatformAndUserId($item->platform, $item->u_id, $item->id);
            }
        }
    }

    public static function delUserPlatform(McUserPlatform $mcUserPlatform, $item)
    {
        $mcUserPlatform->delModel($item->id);
        $mcUserPlatform->delSortCreatedAtZRemMembers($item->id);

        $mcUserPlatform->delByPlatformAndUserId($item->platform, $item->u_id);
        $mcUserPlatform->delByPlatformAndUserIdAndWxOpenid($item->platform, $item->u_id, $item->wx_openid);
        $mcUserPlatform->delByPlatformAndLoginToken($item->platform, $item->login_token);
    }

    public static function club(McPlaywClub $mcPlaywClub, $item)
    {
        $mcPlaywClub->setModel($item->id, (array) $item);

        if ($timestamp = strtotime($item->created_at)) {
            $mcPlaywClub->setSortCreatedAt($timestamp, $item->id);
        }
    }

    public static function delClub(McPlaywClub $mcPlaywClub, $item)
    {
        $mcPlaywClub->delModel($item->id);
        $mcPlaywClub->delSortCreatedAtZRemMembers($item->id);
    }

    public static function clubGroup(McPlaywClubGroup $mcPlaywClubGroup, $item, McPlaywClub $mcPlaywClub)
    {
        $mcPlaywClubGroup->setModel($item->id, (array) $item);

        if ($timestamp = strtotime($item->created_at)) {
            $mcPlaywClubGroup->setSortCreatedAt($timestamp, $item->id);
            // Club add group
            if ($item->club_id) {
                $mcPlaywClub->setSortCreatedAtByGroupId($item->club_id, $timestamp, $item->id);
            }
        }
    }

    /**
     * @param mixed $item
     */
    public static function delClubGroup(McPlaywClubGroup $mcPlaywClubGroup, $item, McPlaywClub $mcPlaywClub)
    {
        $mcPlaywClubGroup->delModel($item->id);
        $mcPlaywClubGroup->delSortCreatedAtZRemMembers($item->id);
        $mcPlaywClub->delSortCreatedAtByGroupIdZRemMembers($item->club_id, $item->id);
    }

    public static function clubProject(McPlaywClubProject $mcPlaywClubProject, $item)
    {
        $mcPlaywClubProject->setModel($item->id, (array) $item);

        if ($timestamp = strtotime($item->created_at)) {
            $mcPlaywClubProject->setSortCreatedAt($timestamp, $item->id);
        }
    }

    public static function delClubProject(McPlaywClubProject $mcPlaywClubProject, $item)
    {
        $mcPlaywClubProject->delModel($item->id);
        $mcPlaywClubProject->delSortCreatedAtZRemMembers($item->id);
    }

    public static function clubBoss(McPlaywClubBoss $mcPlaywClubBoss, $item, McUser $mcUser, McPlaywClub $mcPlaywClub)
    {
        $mcPlaywClubBoss->setModel($item->id, (array) $item);

        if ($timestamp = strtotime($item->created_at)) {
            $mcPlaywClubBoss->setSortCreatedAt($timestamp, $item->id);

            // 用户 add 老板
            if ($item->u_id) {
                $mcUser->setBossListSortCreatedAtByClubId($item->club_id, $item->u_id, $timestamp, $item->id);
            }
            // 俱乐部 add 老板
            if ($item->club_id) {
                $mcPlaywClub->setBossListSortCreatedAtByClubId($item->club_id, $timestamp, $item->id);
            }
        }
    }

    public static function delClubBoss(McPlaywClubBoss $mcPlaywClubBoss, $item, McUser $mcUser, McPlaywClub $mcPlaywClub)
    {
        $mcPlaywClubBoss->delModel($item->id);
        $mcPlaywClubBoss->delSortCreatedAtZRemMembers($item->id);

        // 用户 remove boss
        $mcUser->delBossListSortCreatedAtByClubId($item->club_id, $item->u_id, $item->id);

        // 俱乐部 remove boss
        $mcPlaywClub->delBossListSortCreatedAtByClubIdPaginateZRemMembers($item->club_id, $item->id);
    }

    public static function clubOrder(McPlaywClubOrder $mcPlaywClubOrder, $item, McUser $mcUser, McPlaywClub $mcPlaywClub)
    {
        # id
        $mcPlaywClubOrder->setModel($item->id, (array) $item);

        if ($timestamp = strtotime($item->created_at)) {
            $mcPlaywClubOrder->setSortCreatedAt($timestamp, $item->id);

            if ($item->u_id) {
                $mcUser->setOrderListSortCreatedAtByClubId($item->club_id, $item->u_id, $timestamp, $item->id);
            }

            if ($item->club_id) {
                $mcPlaywClub->setOrderListSortCreatedAtByClubId($item->club_id, $timestamp, $item->id);
            }
        }
    }

    public static function delClubOrder(McPlaywClubOrder $mcPlaywClubOrder, $item, McUser $mcUser, McPlaywClub $mcPlaywClub)
    {
        # id
        $mcPlaywClubOrder->delModel($item->id);

        $mcPlaywClubOrder->delSortCreatedAtZRemMembers($item->id);

        // 用户 remove order
        $mcUser->delOrderListSortCreatedAtByClubId($item->club_id, $item->u_id, $item->id);

        // 俱乐部 remove order
        $mcPlaywClub->delOrderListSortCreatedAtByClubIdZRemMembers($item->club_id, $item->id);
    }
}
