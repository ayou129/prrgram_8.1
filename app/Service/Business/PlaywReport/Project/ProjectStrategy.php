<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service\Business\PlaywReport\Project;

use App\Model\PlaywReportClubProject;
use App\Model\User;
use App\Service\Business\PlaywReport\StairPoint\StairPointService;
use App\Utils\Tools;

class ProjectStrategy implements ProjectInterface
{
    public function getConvertNumber(PlaywReportClubProject $project, User $user): int
    {
        if ($project->convert === PlaywReportClubProject::CONVERT_YES) {
            return $project->convert_number ?? 0;
        }
        return 0;
    }

    public function getPrice(PlaywReportClubProject $project, User $user): float
    {
        // var_dump($project->price_method);
        switch ($project->price_method) {
            case PlaywReportClubProject::PRICE_METHOD_PLAYW:
                return (float) $user->playw_report_club_jiedan_price;
            case PlaywReportClubProject::PRICE_METHOD_FIXED:
                return (float) $project->price_method_fixed;
            case PlaywReportClubProject::PRICE_METHOD_DOUBLE:
                return (float) Tools::mul($project->price_method_double, $user->playw_report_club_jiedan_price);
            default:
                throw new \Exception('不支持的$project->price_method' . $project->price_method);
        }
    }

    public function getClubTakePrice(PlaywReportClubProject $project, User $user): float
    {
        switch ($project->club_take_method) {
            case PlaywReportClubProject::CLUB_TAKE_METHOD_FIXED:
                $val = (float) $project->club_take_method_fixed;
                break;
            case PlaywReportClubProject::CLUB_TAKE_METHOD_RATIO:
                $val = (float) Tools::mul($project->club_take_method_ratio, $this->getPrice(...func_get_args()));
                break;
            default:
                throw new \Exception('不支持的$project->club_take_method' . $project->club_take_method);
        }
        # 特殊情况，如果是多倍，则抽成也翻倍
        //        if ($project->price_method === PlaywReportClubProject::PRICE_METHOD_DOUBLE) {
        //            $val = Tools::mul($project->price_method_double, $val);
        //        }
        return $val < 0 ? 0 : $val;
    }

    public function getZTakePrice(PlaywReportClubProject $project, User $user): float
    {
        //        $stairPointGenAssociationModel = StairPointService::checkStairPointGenAssociation($user, $project);
        //        $jieti_fixed = $jieti_ratio = 0.00;
        //        if ($stairPointGenAssociationModel) {
        //            // 阶梯返点
        //            $stairPointModel = $stairPointGenAssociationModel->stairPoint;
        //            $jieti_fixed = $stairPointModel->point_method_fixed;
        //            $jieti_ratio = $stairPointModel->point_method_ratio;
        //        }
        $price = $this->getPrice(...func_get_args());
        switch ($project->z_take_method) {
            case PlaywReportClubProject::Z_TAKE_METHOD_FIXED:
                // $number = (float) Tools::add($project->z_take_method_fixed, $jieti_fixed);
                $val = (float) $project->z_take_method_fixed;
                break;
            case PlaywReportClubProject::Z_TAKE_METHOD_RATIO:
                // $ratio = (float) Tools::add($project->z_take_method_ratio, $jieti_ratio);
                $val = (float) Tools::mul($project->z_take_method_ratio, $price);
                break;
            default:
                throw new \Exception('不支持的$project->z_take_method' . $project->z_take_method);
        }
        # 特殊情况，如果是多倍，则抽成也翻倍
        //        if ($project->price_method === PlaywReportClubProject::PRICE_METHOD_DOUBLE) {
        //            $val = Tools::mul($project->price_method_double, $val);
        //        }
        return $val < 0 ? 0 : $val;
    }
}
