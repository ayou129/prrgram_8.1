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

namespace App\Service\Business\PlaywReport\Boss;

use App\Constant\ServiceCode;
use App\Exception\ServiceException;
use App\Model\PlaywReportApply;
use App\Model\PlaywReportClub;
use App\Model\PlaywReportClubGroup;
use App\Model\PlaywReportPlaywClubBoss;
use App\Model\User;
use App\Service\Business\PlaywReport\Apply\ApplyService;
use App\Service\Business\PlaywReport\CommonService;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Throwable;

class BossService extends CommonService
{
    /**
     * @Inject
     * @var ApplyService
     */
    public $applyService;

    public function addModelWhere($models, $params, $userModel, $admin = false)
    {
        if (! $admin) {
            // 非管理员，只能看到自己的订单（u_id,z_u_id）
            $models = $models->whereIn('u_id', [$userModel->id]);
        } else {
            // 管理员，可以搜索任何人,如果有条件，则根据条件筛选
            if (isset($params['club_playw_u_ids']) && is_array($params['club_playw_u_ids'])) {
                // 接单陪玩
                $models = $models->whereIn('u_id', $params['club_playw_u_ids']);
            }
        }
        return $models;
    }

    public function getClubBossList($userModel, $params, $request, $admin = false)
    {
        Db::beginTransaction();
        try {
            $result = User::getCacheBossListByIdAndClubId($userModel->playw_report_club_id, $userModel->id, [
            ], (int) $request->input('page', 1), (int) $request->input('size', 10));
            $result = $result->toArray();
            foreach ($result['data'] as &$item) {
                $item['z'] = User::getCacheById($item['u_id']);
                $item['group'] = PlaywReportClubGroup::getCacheById($item['group_id']);
            }
            Db::commit();
            return $result;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function getClubBoss($userModel, $params, $request)
    {
        if (! isset($params['id'])) {
            throw new ServiceException(ServiceCode::ERROR);
        }
        Db::beginTransaction();
        try {
            $model = PlaywReportPlaywClubBoss::getCacheById($params['id'], [
                'z',
                'group',
            ]);
            if (! $model || $model->club_id !== $userModel->playw_report_club_id) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据不存在');
            }

            Db::commit();
            return $model->toArray();
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function putClubBoss($userModel, $params, $request)
    {
        Db::beginTransaction();
        try {
            $model = PlaywReportPlaywClubBoss::where('club_id', $userModel->playw_report_club_id)
                ->where('u_id', $userModel->id)
                ->find($params['id']);
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '该老板不存在');
            }

            // 可以不同群有多个老板
            // 如果改了组，则要判断新组有没有同wx_number的
            $existsModel = PlaywReportPlaywClubBoss::where('club_id', $userModel->playw_report_club_id)
                ->where('wx_number', $params['wx_number'])
                ->where('group_id', $params['group_id'])
                ->where('id', '<>', $params['id'])
                ->exists();
            if ($existsModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '存在相同数据');
            }

            $clubGroupModel = PlaywReportClubGroup::where('club_id', $userModel->playw_report_club_id)
                ->find($params['group_id']);
            if (! $clubGroupModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '群不存在');
            }

            self::bossPutDone($model, $userModel, $params);

            Db::commit();
            return $model->toArray();
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function bossCreateApproval($userModel, $params, $request)
    {
        Db::beginTransaction();
        try {
            $clubGroupModel = PlaywReportClubGroup::where('club_id', $userModel->playw_report_club_id)
                ->find($params['group_id']);
            if (! $clubGroupModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '群不存在');
            }

            $playwBossModel = PlaywReportPlaywClubBoss::where('club_id', $userModel->playw_report_club_id)
                ->where('wx_number', $params['wx_number'])
                ->where('group_id', $params['group_id'])
                ->first();
            if ($playwBossModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '该老板已存在');
            }

            self::bossCreateDone($userModel, $params);

            Db::commit();
            return $playwBossModel;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function postBossWithAutoApproval($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            $playwBossModel = PlaywReportPlaywClubBoss::where('club_id', $user->playw_report_club_id)
                ->where('wx_number', $params['wx_number'])
                ->first();
            if ($playwBossModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '该老板已存在');
            }

            $clubModel = PlaywReportClub::find($user->playw_report_club_id);
            if (! $clubModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '俱乐部不存在');
            }

            $params['club_id'] = $user->playw_report_club_id;

            $applyModel = ApplyService::getApplyHistory($user, $params, PlaywReportApply::TYPE_BOSS_JOIN);
            if (! $applyModel) {
                $applyModel = ApplyService::applyCreate($user, $params, PlaywReportApply::TYPE_BOSS_JOIN);
            }
            if ($clubModel->auto_apply_boss_create !== PlaywReportClub::AUTO_APPLY_BOSS_CREATE_YES) {
                // 创建Apply数据，手动等待审核
            } else {
                // 自动审核
                try {
                    // 防止内部出错，导致外部无法回滚
                    ApplyService::applyPass($applyModel, $user->id);
                } catch (Throwable $ex) {
                    throw $ex;
                }
            }

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function deleteClubBoss($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            $playwBossModel = PlaywReportPlaywClubBoss::where('club_id', $user->playw_report_club_id)
                ->where('u_id', $user->id)
                ->find($params['id']);

            if (! $playwBossModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '该老板不存在');
            }
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '请尝试编辑');
            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public static function bossCreateDone($user, $params)
    {
        $model = new PlaywReportPlaywClubBoss();
        $model->u_id = $user->id;
        $model->club_id = $user->playw_report_club_id;
        $model->group_id = $params['group_id'];
        $model->wx_name = $params['wx_name'];
        $model->wx_number = $params['wx_number'];
        $model->join_at = $params['join_at'];
        $model->save();
    }

    public static function bossPutDone($model, $user, $params)
    {
        $model->u_id = $user->id;
        $model->club_id = $user->playw_report_club_id;
        $model->group_id = $params['group_id'];
        $model->wx_name = $params['wx_name'];
        $model->wx_number = $params['wx_number'];
        $model->join_at = $params['join_at'];
        $model->save();
    }
}
