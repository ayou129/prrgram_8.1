<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Controller\V1\Business\PlaywReport;

use App\Constant\ServiceCode;
use App\Service\Business\PlaywReport\CommonService;
use App\Service\Business\PlaywReport\StairPoint\StairPointService;
use Hyperf\HttpServer\Annotation\AutoController;


class ClubController extends CommonController
{
    public function postClubAdminAdd()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $this->playwService->postClubAdminAdd($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function deleteClubAdmin()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $this->playwService->deleteClubAdmin($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function getClubAdminOrderList()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $result = $this->orderService->orderList($userPlatformModel->user, $params, $this->request, true);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubAdminBossList()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $result = $this->bossService->getClubBossList($userPlatformModel->user, $params, $this->request, true);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubAdminStairPoint()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $result = StairPointService::getClubAdminStairPoints($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function putClubAdminStairPoint()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $result = StairPointService::putClubAdminStairPoint($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function putClubAdminApplyExec()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $this->playwService->putClubAdminApplyExec($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }
}
