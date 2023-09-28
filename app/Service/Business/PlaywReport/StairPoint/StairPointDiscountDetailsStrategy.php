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

namespace App\Service\Business\PlaywReport\StairPoint;

use App\Model\PlaywReportClubOrderStairPointRule;
use App\Model\PlaywReportClubProject;
use App\Model\User;

class StairPointDiscountDetailsStrategy implements StairPointDiscountDetailsStrategyInterface
{
    private $stairPointTypeStrategy;

    private User $userModel;

    private PlaywReportClubProject $projectModel;

    private float $price;

    public function __construct(PlaywReportClubOrderStairPointRule $stairPointRuleModel, $userModel, $projectModel, $price)
    {
        $this->userModel = $userModel;
        $this->projectModel = $projectModel;
        $this->price = $price;
        switch ($stairPointRuleModel->type) {
            case PlaywReportClubOrderStairPointRule::TYPE_DIANDANLIANG:
                $this->stairPointTypeStrategy = new StairPointDiscountDetailsStrategyDiandanliang();
                break;
            case PlaywReportClubOrderStairPointRule::TYPE_JIEDANLIANG:
                $this->stairPointTypeStrategy = new StairPointDiscountDetailsStrategyJiedanliang();
                break;
        }
    }

    public function getDiscountDetails(float $singlePrice): array
    {
        self::checkExcludeUserIds($userModel);
        return $this->stairPointTypeStrategy->getDiscountDetails($singlePrice);
    }

    private function checkExcludeUserIds($userModel): bool
    {
        // 检测是否在排除的用户列表中
        $excludeUserIds = $this->stairPoint->excludeUsers();

        return $this->stairPointTypeStrategy->checkExcludeUserIds($userModel);
    }
}
