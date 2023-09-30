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
use App\Controller\AbstractController;
use App\Exception\ServiceException;
use App\Service\Business\PlaywReport\Apply\ApplyService;
use App\Service\Business\PlaywReport\Boss\BossService;
use App\Service\Business\PlaywReport\Club\ClubService;
use App\Service\Business\PlaywReport\MiniLoginService;
use App\Service\Business\PlaywReport\Order\OrderService;
use App\Service\Business\PlaywReport\PlaywService;
use App\Service\Business\PlaywReport\Project\ProjectService;
use App\Service\Utils\Redis\Redis;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class CommonController extends AbstractController
{
    #[Inject]
    protected OrderService $orderService;

    #[Inject]
    protected BossService $bossService;

    #[Inject]
    protected ClubService $clubService;

    #[Inject]
    protected ProjectService $projectService;

    #[Inject]
    protected ApplyService $applyService;

    #[Inject]
    protected MiniLoginService $miniLoginService;

    #[Inject]
    protected PlaywService $playwService;

    public function getOptions()
    {
        $result = $this->playwService->getOptions();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function postConsoleLog()
    {
        $params = $this->getRequestAllFilter();
        $params['ip'] = $this->request->getUri()
            ->getHost();
        /**
         * @var \App\Model\UserPlatform $userPlatformModel
         */
        $userPlatformModel = $this->miniLoginService->getUserPlatformModelByToken($params);
        $user_id = $userPlatformModel ? $userPlatformModel->u_id : '';
        $data = [
            'context' => $params['context'],
            'user_id' => $user_id,
            'ip' => $params['ip'],
        ];
        $playwReportRedis = new Redis();
        $playwReportRedis->setUserConsoleLog($data);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    /**
     * API测试用例准备.
     */
    public function testPrepare()
    {
        $params = $this->getRequestAllFilter();
        $redis = make(\Hyperf\Redis\Redis::class);
        if (! isset($params['phones'])) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
        }
        if (! isset($params['club_names'])) {
            $params['club_names'] = [
                'JJ俱乐部',
                'KK俱乐部',
            ];
        }
        $result = [];
        $users = Db::table('user')
            ->whereIn('phone', $params['phones'])
            ->get();
        foreach ($users as $user) {
            $result['delete_user_' . $user->id . 'user_platform'] = Db::table('user_platform')
                ->where('u_id', $user->id)
                ->delete();
            $result['delete_user_' . $user->id . 'order'] = Db::table('playw_report_club_order')
                ->where('u_id', $user->id)
                ->delete();
        }
        $clubs = Db::table('playw_report_club')
            ->whereIn('name', $params['club_names'])
            ->get();
        foreach ($clubs as $club) {
            $result['delete_club_' . $club->id . 'order'] = Db::table('playw_report_club_order')
                ->where('club_id', $club->id)
                ->delete();
        }
        $result['delete_club_names_number'] = Db::table('playw_report_club')
            ->whereIn('name', $params['club_names'])
            ->delete();
        $result['delete_user_count'] = Db::table('user')
            ->whereIn('phone', $params['phones'])
            ->delete();

        $result['redis_delete_status'] = $redis->flushall();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }
}
