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

namespace App\Service\Business\PlaywReport\Apply;

use App\Model\PlaywReportApply;
use App\Service\Business\PlaywReport\Boss\BossService;
use App\Service\Business\PlaywReport\Club\ClubService;
use App\Utils\Tools;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;

class ApplyService
{
    /**
     * @Inject
     * @var ClubService
     */
    public $clubService;

    public static function applyCreate($user, $params, int $type)
    {
        $model = new PlaywReportApply();
        $model->u_id = $user->id;
        if ($params['club_id']) {
            $model->club_id = $params['club_id'];
        }
        $model->type = $type;
        $model->params = $params;
        $model->status = PlaywReportApply::STATUS_DEFAULT;
        $model->save();
        return $model;
    }

    public function getApply($userModel, $params, $request)
    {
        return Db::table((new PlaywReportApply())->getTable())
            ->where('u_id', $userModel->id)
            ->where('status', $params['status'])
            ->where('type', $params['type'])
            ->with('club')
            ->first();
    }

    public static function getApplyHistory($user, $params, int $type)
    {
        return Db::table((new PlaywReportApply())->getTable())
            ->where('u_id', $user->id)
            ->where('type', $type)
            ->where('status', PlaywReportApply::STATUS_DEFAULT)
            ->first();
    }

    public static function getApplyList($club_id, int $type, $request)
    {
        return Db::table((new PlaywReportApply())->getTable())
            ->where('club_id', $club_id)
            ->where('type', (int) $type ?? PlaywReportApply::TYPE_BOSS_JOIN)
            ->orderBy('status', 'asc')
            ->paginate((int) $request->input('size', 10));
    }

    public static function getApplyBadge(int $club_id)
    {
        $models = Db::table((new PlaywReportApply())->getTable())
            ->where('club_id', $club_id)
            ->where(function ($query) {
                $query->where('status', PlaywReportApply::STATUS_DEFAULT);
            });
        return $models->get();
    }

    public static function applyPass(PlaywReportApply $applyModel, $exec_u_id)
    {
        $applyModel->status = PlaywReportApply::STATUS_YES;
        $applyModel->exec_u_id = $exec_u_id;
        $applyModel->exec_at = Tools::getNowDate();
        $applyModel->save();
        switch ($applyModel->type) {
            case PlaywReportApply::TYPE_CLUB_JOIN:
                ClubService::clubJoinDone($applyModel->user, $applyModel->params);
                break;
            case PlaywReportApply::TYPE_CLUB_LEAVE:
                ClubService::clubLeaveDone($applyModel->user, $applyModel->params);
                break;
            case PlaywReportApply::TYPE_BOSS_JOIN:
                BossService::bossCreateDone($applyModel->user, $applyModel->params);
                break;
            default:
                throw new Exception('未知的申请类型$type:' . $applyModel->type);
        }
        return $applyModel;
    }

    public static function applyRefuse(PlaywReportApply $applyModel, $exec_u_id)
    {
        $applyModel->status = PlaywReportApply::STATUS_NO;
        $applyModel->exec_u_id = $exec_u_id;
        $applyModel->exec_at = Tools::getNowDate();
        $applyModel->save();
    }

    public function applyUpdate($user, $params, $request)
    {
    }

    public function applyDelete($user, $params, $request)
    {
    }
}
