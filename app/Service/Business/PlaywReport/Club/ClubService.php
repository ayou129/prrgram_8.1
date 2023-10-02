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

namespace App\Service\Business\PlaywReport\Club;

use App\Constant\ServiceCode;
use App\Exception\ServiceException;
use App\Model\PlaywReportApply;
use App\Model\PlaywReportClub;
use App\Model\PlaywReportClubOrder;
use App\Model\PlaywReportClubProject;
use App\Model\PlaywReportPlaywClubBoss;
use App\Model\User;
use App\Service\Business\PlaywReport\Apply\ApplyService;
use App\Service\Business\PlaywReport\CommonService;
use App\Utils\Tools;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Throwable;

class ClubService extends CommonService
{
    /**
     * @Inject(lazy=true)
     * @var ApplyService
     */
    public $applyService;

    public function getClubById($params, $request)
    {
        if (! $params['id']) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], 'id不存在');
        }
        $clubModel = PlaywReportClub::getCacheById($params['id']);
        if (! $clubModel) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '俱乐部不存在');
        }

        return $clubModel->toArray();
    }

    public function getClubByName($params, $request)
    {
        if (! $params['name']) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], 'name不存在');
        }
        $clubModel = Db::table((new PlaywReportClub())->getTable())
            ->where('name', $params['name'])
            ->first();
        if (! $clubModel) {
            return [];
        }

        return $clubModel;
    }

    public function getClubRanking(User $userModel, $params, $request)
    {
        // 按照时间
        // 按照类型
        $orderModel = Db::table((new PlaywReportClubOrder)->getTable())
            ->where('club_id', $userModel->playw_report_club_id);
        $bossModel = PlaywReportPlaywClubBoss::where('club_id', $userModel->playw_report_club_id)
            ->with(['z']);
        $orderModel = $this->addModelTimeWhere($orderModel, $params);
        $bossModel = $this->addModelTimeWhere($bossModel, $params);
        $data = [];
        switch ($params['type']) {
            case 1:
                // 1 接单数量
                // order 根据u_id分组，取sum(convert_number)
                $temp = $orderModel->groupBy('u_id')
                    ->selectRaw('u_id, sum(convert_number) as score')
                    ->orderBy('score', 'desc')
                    ->get();
                foreach ($temp as $item) {
                    $user = User::getCacheById($item->u_id);
                    User::addAttrText($user);
                    $data[] = [
                        'user' => $user,
                        'score' => $item->score,
                    ];
                }
                break;
            case 2:
                // 2 点单数量
                // order order 根据z_u_id分组，取sum(convert_number)
                $temp = $orderModel->groupBy('z_u_id')
                    ->selectRaw('z_u_id, sum(convert_number) as score')
                    ->orderBy('score', 'desc')
                    ->get();
                foreach ($temp as $item) {
                    $user = User::getCacheById($item->z_u_id);
                    User::addAttrText($user);
                    $data[] = [
                        'user' => $user,
                        'score' => $item->score,
                    ];
                }
                break;
            case 3:
                // 3 礼物数量
                // order 条件type=GIFT，根据u_id分组，取sum(number)
                $temp = $orderModel->where('type', PlaywReportClubProject::TYPE_GIFT)
                    ->groupBy('u_id')
                    ->selectRaw('u_id, sum(number) as score')
                    ->orderBy('score', 'desc')
                    ->get();
                foreach ($temp as $item) {
                    $user = User::getCacheById($item->u_id);
                    User::addAttrText($user);
                    $data[] = [
                        'user' => $user,
                        'score' => $item->score,
                    ];
                }
                break;
            case 4:
                // 4 老板
                // boss 根据u_id分组，取count(*)
                $temp = $bossModel->groupBy('u_id')
                    ->selectRaw('u_id, count(*) as score')
                    ->orderBy('score', 'desc')
                    ->get();
                foreach ($temp as $item) {
                    $user = User::getCacheById($item['u_id']);
                    User::addAttrText($user);
                    $data[] = [
                        'z' => $user,
                        'score' => $item['score'],
                    ];
                }
                break;
            default:
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '类型有误');
        }

        return $data;
    }

    //    public function clubJoinApproval($user, $params, $request)
    //    {
    //        Db::beginTransaction();
    //        try {
    //            if ($user->playw_report_club_id) {
    //                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '已存在俱乐部');
    //            }
    //            $clubModel = PlaywReportClub::where('name', $params['name'])->first();
    //            if (! $clubModel) {
    //                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '俱乐部不存在');
    //            }
    //
    //            self::clubJoinDone($user, $clubModel->id);
    //
    //            Db::commit();
    //            return true;
    //        } catch (\Throwable $ex) {
    //            Db::rollBack();
    //            throw $ex;
    //        }
    //    }

    public function clubJoinWithAutoApproval($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            if ($user->playw_report_club_id) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '已存在俱乐部');
            }
            $clubModel = Db::table('playw_report_club')
                ->where('name', $params['name'])
                ->first();
            if (! $clubModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '俱乐部不存在');
            }

            $params['club_id'] = $clubModel->id;

            $applyModel = ApplyService::getApplyHistory($user, $params, PlaywReportApply::TYPE_CLUB_JOIN);
            if (! $applyModel) {
                $applyModel = ApplyService::applyCreate($user, $params, PlaywReportApply::TYPE_CLUB_JOIN);
            }
            if ($clubModel->auto_apply_club_join === PlaywReportClub::AUTO_APPLY_CLUB_JOIN_YES) {
                // 自动审核
                ApplyService::applyPass($applyModel, $user->id);
            }
            // 创建Apply数据，手动等待审核

            Db::commit();
            return $clubModel;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    //    public function clubLeaveApproval($user, $params, $request)
    //    {
    //        Db::beginTransaction();
    //        try {
    //            if (! $user->playw_report_club_id) {
    //                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '未加入俱乐部');
    //            }
    //            if (! $user->club) {
    //                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '俱乐部不存在');
    //            }
    //
    //            self::clubLeaveDone($user, $user->playw_report_club_id);
    //
    //            Db::commit();
    //            return true;
    //        } catch (\Throwable $ex) {
    //            Db::rollBack();
    //            throw $ex;
    //        }
    //    }

    //    public function clubLeaveWithAutoApproval($user, $params, $request)
    //    {
    //        Db::beginTransaction();
    //        try {
    //            if (! $user->playw_report_club_id) {
    //                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '未加入俱乐部');
    //            }
    //            if (! $user->club) {
    //                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '俱乐部不存在');
    //            }
    //
    //            $params['club_id'] = $user->playw_report_club_id;
    //
    //            $applyModel = ApplyService::getApplyHistory($user, $params, PlaywReportApply::TYPE_CLUB_JOIN);
    //            if (! $applyModel) {
    //                $applyModel = ApplyService::applyCreate($user, $params, PlaywReportApply::TYPE_CLUB_JOIN);
    //            }
    //            if ($user->club->auto_apply_club_join === PlaywReportClub::AUTO_APPLY_CLUB_JOIN_YES) {
    //                // 自动审核
    //                ApplyService::applyPass($applyModel, $user->id);
    //            }
    //            // 创建Apply数据，手动等待审核
    //
    //            Db::commit();
    //            return true;
    //        } catch (\Throwable $ex) {
    //            Db::rollBack();
    //            throw $ex;
    //        }
    //    }

    public function clubCreate($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            if ($user->playw_report_club_id) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '已存在俱乐部');
            }

            $clubModel = Db::table('playw_report_club')
                ->where('name', $params['name'])
                ->first();
            if ($clubModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '俱乐部已存在');
            }

            $clubModel = new PlaywReportClub();
            $clubModel->u_id = $user->id;
            $clubModel->leave_old_u_id = $user->id;
            $clubModel->name = $params['name'];
            $clubModel->save();

            $user->playw_report_club_id = $clubModel->id;
            $user->playw_report_club_admin = User::PLAYW_REPORT_CLUB_ADMIN_YES;
            $user->playw_report_club_join_at = Tools::getNowDate();
            $user->save();
            Db::commit();
            return $clubModel;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function putClubLeave($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            // 1.陪玩退出俱乐部，提交申请
            // 2.创始人解散俱乐部，检查相应的数据，

            $params['club_id'] = $user->playw_report_club_id;
            if ($user->id === $user->club->u_id) {
                // 创始人
                // 检查所有陪玩是否都已经退出
                $clubUserExists = User::where('playw_report_club_id', $user->playw_report_club_id)
                    ->where('id', '!=', $user->id)
                    ->exists();
                if ($clubUserExists) {
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '请遣散所有成员');
                }
                self::clubLeaveDone($user);
            } else {
                // 陪玩
                // 检查是否已经提交过申请
                $applyModel = ApplyService::getApplyHistory($user, $params, PlaywReportApply::TYPE_CLUB_LEAVE);
                if (! $applyModel) {
                    $applyModel = ApplyService::applyCreate($user, $params, PlaywReportApply::TYPE_CLUB_LEAVE);
                }
            }

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public static function clubJoinDone($userModel, $params)
    {
        $userModel->playw_report_club_id = $params['club_id'];
        $userModel->playw_report_club_join_at = Tools::getNowDate();
        $userModel->save();
        return true;
    }

    public static function clubLeaveDone($userModel)
    {
        $userModel->playw_report_club_id = null;
        $userModel->playw_report_club_join_at = null;
        $userModel->save();
    }

    public static function playwRemove($userModel)
    {
        $userModel->playw_report_club_id = null;
        $userModel->playw_report_club_join_at = null;
        $userModel->save();
    }
}
