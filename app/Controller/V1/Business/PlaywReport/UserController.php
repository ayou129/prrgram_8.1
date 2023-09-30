<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Controller\V1\Business\PlaywReport;

use App\Constant\ServiceCode;
use App\Exception\ServiceException;
use App\Model\PlaywReportClub;
use Hyperf\HttpServer\Annotation\AutoController;


class UserController extends CommonController
{
    public function registerAndLoginByPhone()
    {
        $params = $this->getRequestAllFilter();
        if (! isset($params['wx_login_code'])) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
        }
        $params['ip'] = $this->request->getUri()->getHost();
        $result = $this->miniLoginService->registerAndLoginByPhone($params);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    //    public function miniLogin()
    //    {
    //        $params = $this->getRequestAllFilter();
    //        if (! isset($params['code'])) {
    //            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
    //        }
    //        $params['ip'] = $this->request->getUri()->getHost();
    //        $result = $this->miniLoginService->miniLogin($params);
    //        return $this->responseJson(ServiceCode::SUCCESS, $result);
    //    }

    public function checkUserPlatformExists()
    {
        $params = $this->getRequestAllFilter();
        $result = $this->miniLoginService->checkUserPlatformExists($params);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function getUserPlaywReportInfo()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        $userPlatformModel->user->clubJoinApply;
        $user = $userPlatformModel->user->toArray();
        $user['club'] = PlaywReportClub::getCacheById($userPlatformModel->user->playw_report_club_id);
        return $this->responseJson(ServiceCode::SUCCESS, $user);
    }

    public function putUserPlaywReportInfo()
    {
        $params = $this->getRequestAllFilter();
        $userPlatformModel = $this->miniLoginService->checkAndReletToken($params);
        if (! isset($params['playw_name']) || ! $params['playw_name'] || (strlen($params['playw_name']) > 50)) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
        }
        $userPlatformModel->user->playw_report_playwname = $params['playw_name'];
        if (isset($params['club_jiedan_price'])) {
            if (! is_numeric($params['club_jiedan_price'])) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }
            $userPlatformModel->user->playw_report_club_jiedan_price = $params['club_jiedan_price'];
        }
        $userPlatformModel->user->save();
        return $this->responseJson(ServiceCode::SUCCESS);
    }
}
