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

// use App\Model\PlaywReportClubGroup;
// use App\Model\PlaywReportClubProject;
// use App\Service\Utils\Redis\PlaywReport\McPlaywReportClub;
// use App\Service\Utils\Redis\PlaywReport\McPlaywReportClubGroup;
// use App\Service\Utils\Redis\PlaywReport\McPlaywReportClubOrder;
// use App\Service\Utils\Redis\PlaywReport\McPlaywReportClubProject;
// use App\Service\Utils\Redis\PlaywReport\McPlaywReportPlaywClubBoss;
use App\Service\Utils\Redis\PlaywReport\McUser;
use App\Service\Utils\Redis\PlaywReport\McUserPlatform;
use Hyperf\DbConnection\Db;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\OnStart;
use Hyperf\Redis\Redis;

#[Listener]
class ModelCacheInitLoaderListener implements ListenerInterface
{
    public function listen(): array
    {
        return [
            // OnStart::class,
        ];
    }

    public function process(object $event): void
    {
        // $green = "\033[32m";
        // $reset = "\033[0m";
        // echo '---------- Cache init loading...  ---------' . PHP_EOL;
        // $redis = \Hyperf\Support\make(Redis::class);
        // $redis->flushAll();
        // //        $redis->pipeline();
        //
        // $mcUser = new McUser($redis);
        // $mcUserPlatform = new McUserPlatform($redis);
        // $mcPlaywClub = new McPlaywReportClub($redis);
        // $mcPlaywClubGroup = new McPlaywReportClubGroup($redis);
        // $mcPlaywClubBoss = new McPlaywReportPlaywClubBoss($redis);
        // $mcPlaywClubOrder = new McPlaywReportClubOrder($redis);
        // $mcPlaywClubProject = new McPlaywReportClubProject($redis);
        // $redis->pipeline();
        // // load model
        // echo 'model user loading...';
        // $usersArray = Db::table('user')
        //     ->whereNull('deleted_at')
        //     ->get();
        // foreach ($usersArray as $item) {
        //     ModelCacheListener::user($mcUser, $item, $mcPlaywClub);
        // }
        // echo 'model user_platform loading...';
        // $user_platformsArray = Db::table('user_platform')
        //     ->whereNull('deleted_at')
        //     ->get();
        // foreach ($user_platformsArray as $item) {
        //     ModelCacheListener::userPlatform($mcUserPlatform, $item);
        // }
        // echo 'model playw_club loading...';
        // $playw_report_clubsArray = Db::table('playw_report_club')
        //     ->whereNull('deleted_at')
        //     ->get();
        // foreach ($playw_report_clubsArray as $item) {
        //     ModelCacheListener::club($mcPlaywClub, $item);
        // }
        // echo 'model playw_club_group loading...';
        // $playw_report_club_groupsArray = Db::table((new PlaywReportClubGroup())->getTable())
        //     ->whereNull('deleted_at')
        //     ->get();
        // foreach ($playw_report_club_groupsArray as $item) {
        //     ModelCacheListener::clubGroup($mcPlaywClubGroup, $item, $mcPlaywClub);
        // }
        // echo 'model playw_club_project loading...';
        // $playw_report_club_projectsArray = Db::table((new PlaywReportClubProject())->getTable())
        //     ->whereNull('deleted_at')
        //     ->get();
        // foreach ($playw_report_club_projectsArray as $item) {
        //     ModelCacheListener::clubProject($mcPlaywClubProject, $item, $mcPlaywClub);
        // }
        // echo 'model playw_club_boss loading...';
        // $playw_report_playw_club_bosssArray = Db::table('playw_report_playw_club_boss')
        //     ->whereNull('deleted_at')
        //     ->get();
        // foreach ($playw_report_playw_club_bosssArray as $item) {
        //     ModelCacheListener::clubBoss($mcPlaywClubBoss, $item, $mcUser, $mcPlaywClub);
        // }
        // echo 'model playw_club_order loading...';
        // $playw_report_club_ordersArray = Db::table('playw_report_club_order')
        //     ->whereNull('deleted_at')
        //     ->get();
        // foreach ($playw_report_club_ordersArray as $item) {
        //     ModelCacheListener::clubOrder($mcPlaywClubOrder, $item, $mcUser, $mcPlaywClub);
        // }
        // $redis->exec();
        // echo $green . '     done' . $reset . PHP_EOL;

        // echo '---------- Cache init load done  ---------' . PHP_EOL;
    }
}
