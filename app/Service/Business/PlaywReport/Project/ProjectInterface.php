<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service\Business\PlaywReport\Project;

use App\Model\PlaywReportClubProject;
use App\Model\User;

interface ProjectInterface
{
    // 折单
    public function getConvertNumber(PlaywReportClubProject $project, User $user): int;

    // 单价
    public function getPrice(PlaywReportClubProject $project, User $user): float;

    // 俱乐部抽成
    public function getClubTakePrice(PlaywReportClubProject $project, User $user): float;

    // 直属返点
    public function getZTakePrice(PlaywReportClubProject $project, User $user): float;
}
