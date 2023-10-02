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

namespace App\Controller\V1\Business\PlaywReport;

use App\Constant\ServiceCode;
use App\Exception\ServiceException;
use App\Model\PlaywReportApply;
use App\Model\PlaywReportClub;
use App\Service\Business\PlaywReport\CommonService;

class PlaywController extends CommonController
{
    public function checkClubIdStatus()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        $result = [
            'status' => CommonService::checkClubIdStatus($userPlatformModel->user, false),
            'club' => PlaywReportClub::getCacheById($userPlatformModel->user->playw_report_club_id),
        ];
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubById()
    {
        $params = $this->getRequestAllFilter();
        $result = $this->clubService->getClubById($params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubByName()
    {
        $params = $this->getRequestAllFilter();
        $result = $this->clubService->getClubByName($params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubRanking()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        $result = $this->clubService->getClubRanking($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function checkPlaywNameStatus()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        $result = [
            'status' => CommonService::checkPlaywName($userPlatformModel->user, false),
        ];
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function putClubLeave()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $this->clubService->putClubLeave($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function getClubAdminPlaywToken()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $result = $this->playwService->getClubAdminPlaywToken($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function clubJoin()
    {
        $params = $this->getRequestAllFilter();
        if (! isset($params['type'], $params['name'])) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
        }
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkPlaywName($userPlatformModel->user);
        switch ($params['type']) {
            case 'join':
                $result = $this->clubService->clubJoinWithAutoApproval($userPlatformModel->user, $params, $this->request);
                break;
            case 'create':
                $result = $this->clubService->clubCreate($userPlatformModel->user, $params, $this->request);
                break;
            default:
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
        }
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getPageIndexData()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->playwService->getPageIndexData($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getPageMyData()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->playwService->getPageMyData($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getPageStatisticsData()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->playwService->getPageStatisticsData($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubBoss()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->bossService->getClubBoss($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function putClubBoss()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->bossService->putClubBoss($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubBossList()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->bossService->getClubBossList($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function postClubBoss()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $this->bossService->bossCreateApproval($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function postBossWithAutoApproval()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $this->bossService->postBossWithAutoApproval($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function deleteClubBoss()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $this->bossService->deleteClubBoss($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function getOrderList()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->orderService->orderList($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubPageOptions()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->playwService->getClubPageOptions($userPlatformModel->user, $params, $this->request);

        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getOrder()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->orderService->getOrderById($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function orderList()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->orderService->orderList($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function orderCreate()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $this->orderService->checkUserPlaywReportClubJiedanPrice($userPlatformModel->user);
        $this->orderService->orderCreate($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function putOrder()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $this->orderService->putOrder($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function deleteOrder()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $this->orderService->deleteOrder($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function putOrderJiezhang()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $this->orderService->putOrderJqJiezhang($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function putOrderFandian()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $this->orderService->putOrderJqFandian($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    // 订单计算
    public function orderCalculate()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $this->orderService->checkUserPlaywReportClubJiedanPrice($userPlatformModel->user);
        $result = $this->orderService->orderCalculate($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubAdminPlaywList()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->playwService->getClubAdminPlaywList($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubAdminPlaywListAll()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->playwService->getClubAdminPlaywListAll($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubAdminProjectListAll()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->playwService->getClubAdminProjectListAll($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function clubAdminPlaywRemove()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $result = $this->playwService->clubAdminPlaywRemove($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubAdminGroupList()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->playwService->getClubAdminGroupList($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function postClubAdminGroup()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $this->playwService->postClubAdminGroup($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function putClubAdminGroup()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $this->playwService->putClubAdminGroup($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function deleteClubAdminGroup()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $this->playwService->deleteClubAdminGroup($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function getClubAdminProject()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->playwService->getClubAdminProject($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubAdminProjectList()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        $result = $this->playwService->getClubAdminProjectList($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function postClubAdminProject()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $this->playwService->postClubAdminProject($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function putClubAdminProject()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $this->playwService->putClubAdminProject($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function deleteClubAdminProject()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $this->playwService->deleteClubAdminProject($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function getClubApplyList()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $result = $this->playwService->getClubApplyList($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getClubApply()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkPlaywName($userPlatformModel->user);
        //        if (! isset($params['id'])) {
        //            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '请输入id');
        //        }
        $params['type'] = PlaywReportApply::TYPE_CLUB_JOIN;
        $params['status'] = PlaywReportApply::STATUS_DEFAULT;
        $result = $this->applyService->getApply($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function putClubJoinApplyCancel()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        $this->playwService->putClubJoinApplyCancel($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function getClubAdminSetting()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $result = $this->playwService->getClubAdminSetting($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function putClubAdminSetting()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        CommonService::checkClubIdStatus($userPlatformModel->user);
        CommonService::checkPlaywName($userPlatformModel->user);
        CommonService::checkIsClubAdmin($userPlatformModel->user);
        $result = $this->playwService->putClubAdminSetting($userPlatformModel->user, $params, $this->request);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }
}
