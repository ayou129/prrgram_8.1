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
