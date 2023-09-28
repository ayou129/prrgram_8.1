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

class ProjectManager
{
    private PlaywReportClubProject $project;

    private User $user;

    private ProjectInterface $strategy;

    private float $unitPrice;

    private float $allPrice;

    private int $number;

    public function __construct(PlaywReportClubProject $project, User $user)
    {
        if ($project->type === PlaywReportClubProject::TYPE_DEFAULT) {
            $this->strategy = new ProjectStrategyGame();
        } else {
            $this->strategy = new ProjectStrategyGift();
        }
        $this->project = $project;
        $this->user = $user;

        // $this->unitPrice = $this->strategy->computeUnitPrice();
    }

    public function getConvertNumber()
    {
        return $this->strategy->getConvertNumber($this->project, $this->user);
    }

    // 每单价格
    public function getPrice(): float
    {
        return $this->strategy->getPrice($this->project, $this->user);
    }

    // 俱乐部每单抽成
    public function getClubTakePrice(): float
    {
        return $this->strategy->getClubTakePrice($this->project, $this->user);
    }

    public function getZTakePrice(): float
    {
        return $this->strategy->getZTakePrice($this->project, $this->user);
    }
}
