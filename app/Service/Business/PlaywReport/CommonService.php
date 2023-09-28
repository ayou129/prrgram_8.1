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

namespace App\Service\Business\PlaywReport;

use App\Constant\ServiceCode;
use App\Exception\ServiceException;
use App\Model\User;
use Carbon\Carbon;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Coroutine;

class CommonService
{
    /**
     * 一个为数据模型类(例如BossModel.php)增加可以复用的时间条件(where)的方法.
     * @param mixed $models
     * @param mixed $params
     * @param string $timeField
     */
    public function addModelTimeWhere($models, $params, $timeField = 'created_at')
    {
        $container = ApplicationContext::getContainer();
        $currentTime = $container->get(Coroutine::class)->inCoroutine() ? time() : microtime(true);
        if (isset($params['formTimeSearchMode']) && $params['formTimeSearchMode']) {
            switch ($params['formTimeSearchMode']) {
                case '0':
                    // 上方已经过滤了
                    break;
                case '1':
                    // 本月
                    $params['start_at'] = Carbon::now()->setTimezone('Asia/Shanghai')->setTimestamp($currentTime)->startOfMonth()->startOfDay()->format('Y-m-d H:i:s');
                    $params['end_at'] = Carbon::now()->setTimezone('Asia/Shanghai')->setTimestamp($currentTime)->endOfMonth()->endOfDay()->format('Y-m-d H:i:s');
                    $models = $models->where(function ($query) use ($params, $timeField) {
                        $query->where($timeField, '>=', $params['start_at'])
                            ->where($timeField, '<=', $params['end_at']);
                    });
                    break;
                case '2':
                    // 本周
                    $params['start_at'] = Carbon::now()->setTimezone('Asia/Shanghai')->setTimestamp($currentTime)->startOfWeek()->startOfDay()->format('Y-m-d H:i:s');
                    $params['end_at'] = Carbon::now()->setTimezone('Asia/Shanghai')->setTimestamp($currentTime)->endOfWeek()->endOfDay()->format('Y-m-d H:i:s');
                    $models = $models->where(function ($query) use ($params, $timeField) {
                        $query->where($timeField, '>=', $params['start_at'])
                            ->where($timeField, '<=', $params['end_at']);
                    });
                    break;
                case '3':
                    // 某一天
                    $day = $params['day'];
                    if (! isset($day) || ! $day) {
                        $day = date('Y-m-d');
                    }
                    $params['start_at'] = $day . ' 00:00:00';
                    $params['end_at'] = $day . ' 23:59:59';
                    var_dump($params);
                    $models = $models->where(function ($query) use ($params, $timeField) {
                        $query->where($timeField, '>=', $params['start_at'])
                            ->where($timeField, '<=', $params['end_at']);
                    });
                    break;
                case '4':
                default:
                    $params['start_at'] = Carbon::now()
                        ->setTimezone('Asia/Shanghai')
                        ->setTimestamp(strtotime($params['start_at']))
                        ->startOfDay()
                        ->format('Y-m-d H:i:s');
                    $params['end_at'] = Carbon::now()
                        ->setTimezone('Asia/Shanghai')
                        ->setTimestamp(strtotime($params['end_at']))
                        ->endOfDay()
                        ->format('Y-m-d H:i:s');
                    $models = $models->where(function ($query) use ($params, $timeField) {
                        $query->where($timeField, '>=', $params['start_at'])
                            ->where($timeField, '<=', $params['end_at']);
                    });
                    break;
            }
        }

        return $models;
    }

    public static function checkClubIdStatus($user, $exception = true)
    {
        if (! $exception) {
            return $user->playw_report_club_id ? true : false;
        }
        if (! $user->playw_report_club_id) {
            throw new ServiceException(ServiceCode::ERROR_PLAYW_REPORT_CLUB_NOT_EXISTS);
        }
    }

    public static function checkPlaywName($user, $exception = true)
    {
        if (! $exception) {
            return $user->playw_report_playwname ? true : false;
        }
        if (! $user->playw_report_playwname) {
            throw new ServiceException(ServiceCode::ERROR_PLAYW_REPORT_PLAYW_NAME_NOT_EXISTS);
        }
    }

    public static function checkIsClubAdmin(User $user, $exception = true)
    {
        $status = $user->playw_report_club_admin === User::PLAYW_REPORT_CLUB_ADMIN_YES;
        if (! $status && $exception) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '无权限操作');
        }
        return $status;
    }

    public static function checkIsClubOwn(User $user, $exception = true)
    {
        $status = $user->id != $user->club->u_id;
        if (! $status && $exception) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '无权限操作');
        }
        return $status;
    }
}
