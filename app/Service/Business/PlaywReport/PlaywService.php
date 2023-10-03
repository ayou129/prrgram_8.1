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
use App\Model\PlaywReportApply;
use App\Model\PlaywReportClub;
use App\Model\PlaywReportClubGroup;
use App\Model\PlaywReportClubOrder;
use App\Model\PlaywReportClubOrderStairPointRule;
use App\Model\PlaywReportClubProject;
use App\Model\PlaywReportPlaywClubBoss;
use App\Model\User;
use App\Service\Business\PlaywReport\Apply\ApplyService;
use App\Service\Business\PlaywReport\Club\ClubService;
use App\Service\Business\PlaywReport\Order\OrderService;
use App\Service\Utils\Redis\Redis;
use App\Utils\Tools;
use Hyperf\DbConnection\Db;
use Throwable;

class PlaywService extends CommonService
{
    // private $miniProgramService;
    //
    // public function __construct()
    // {
    //     $this->miniProgramService = new MiniLoginService();
    // }

    public function getClubAdminPlaywToken($userModel, $params)
    {
        if (! $params['id']) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '请选择用户');
        }
        $model = User::getCacheUserByIdAndClubId($params['id'], $userModel->playw_report_club_id, ['platformMiniprogram']);
        return $model->platformMiniprogram->login_token ?? '';
    }

    public function getStatisticsData($userModel, $params, $u_id = false, $admin = false)
    {
        $orderModels = Db::table((new PlaywReportClubOrder())->getTable())
            ->where('club_id', $userModel->playw_report_club_id);
        //        var_dump($u_id);
        if ($u_id) {
            $orderModels = $orderModels->where(function ($query) use ($u_id) {
                $query->where('u_id', $u_id)
                    ->orWhere('z_u_id', $u_id);
            });
        }
        $orderModels = $this->addModelTimeWhere($orderModels, $params);

        $orderModels = $orderModels->get();

        $jiedan_total_convert_number_default = 0;
        $jiedan_total_number_gift = 0;

        $jiedan_total_jiedan_price_all_default = 0;
        $jiedan_total_jiedan_price_all_gift = 0;

        $jiedan_total_price_all = 0;

        $diandan_total_convert_number_default = 0;
        $diandan_total_number_gift = 0;
        $diandan_total_z_take_price_all = 0;

        $club_price_takes_all = 0;
        /*
         * 总接单量 = u_id
         *      总接单额 = jiedan_price_all
         *      接单总收益 = price_all
         * 总点单量 = z_u_id
         *      总点单额 = jiedan_price_all
         *      点单总收益(返点) = z_take_price_all
         *      礼物单量 number
         * 俱乐部收益 = price_takes_all
         *
         */
        foreach ($orderModels as $orderModel) {
            if ($u_id) {
                if ($orderModel->u_id === $u_id) {
                    //                    var_dump($orderModel->u_id, $u_id);
                    # 接单数据
                    # # 接单量
                    if ($orderModel->type === PlaywReportClubProject::TYPE_DEFAULT) {
                        $jiedan_total_convert_number_default = Tools::add($jiedan_total_convert_number_default, $orderModel->convert_number, 0);
                    } elseif ($orderModel->type === PlaywReportClubProject::TYPE_GIFT) {
                        $jiedan_total_number_gift = Tools::add($jiedan_total_number_gift, $orderModel->number, 0);
                    }

                    # # 接单额(+/礼物)
                    if ($orderModel->type === PlaywReportClubProject::TYPE_DEFAULT) {
                        $jiedan_total_jiedan_price_all_default = Tools::add($jiedan_total_jiedan_price_all_default, $orderModel->jiedan_price_all);
                    } elseif ($orderModel->type === PlaywReportClubProject::TYPE_GIFT) {
                        $jiedan_total_jiedan_price_all_gift = Tools::add($jiedan_total_jiedan_price_all_gift, $orderModel->jiedan_price_all);
                    }

                    # # 接单收益
                    $jiedan_total_price_all = Tools::add($jiedan_total_price_all, $orderModel->price_all);
                }
                if ($orderModel->z_u_id === $u_id) {
                    // var_dump($orderModel->z_u_id, $u_id);
                    # 点单数据
                    # # 点单量
                    if ($orderModel->type === PlaywReportClubProject::TYPE_DEFAULT) {
                        $diandan_total_convert_number_default = Tools::add($diandan_total_convert_number_default, $orderModel->convert_number, 0);
                    } elseif ($orderModel->type === PlaywReportClubProject::TYPE_GIFT) {
                        $diandan_total_number_gift = Tools::add($diandan_total_number_gift, $orderModel->number, 0);
                    }

                    # # 点单收益
                    $diandan_total_z_take_price_all = Tools::add($diandan_total_z_take_price_all, $orderModel->z_take_price_all);
                }
            } else {
                # # 接单量
                if ($orderModel->type === PlaywReportClubProject::TYPE_DEFAULT) {
                    $jiedan_total_convert_number_default = Tools::add($jiedan_total_convert_number_default, $orderModel->convert_number, 0);
                } elseif ($orderModel->type === PlaywReportClubProject::TYPE_GIFT) {
                    $jiedan_total_number_gift = Tools::add($jiedan_total_number_gift, $orderModel->number, 0);
                }

                # # 接单额
                if ($orderModel->type === PlaywReportClubProject::TYPE_DEFAULT) {
                    $jiedan_total_jiedan_price_all_default = Tools::add($jiedan_total_jiedan_price_all_default, $orderModel->jiedan_price_all);
                } elseif ($orderModel->type === PlaywReportClubProject::TYPE_GIFT) {
                    $jiedan_total_jiedan_price_all_gift = Tools::add($jiedan_total_jiedan_price_all_gift, $orderModel->jiedan_price_all);
                }

                # # 接单收益
                $jiedan_total_price_all = Tools::add($jiedan_total_price_all, $orderModel->price_all);

                # 点单数据
                # # 点单量
                if ($orderModel->type === PlaywReportClubProject::TYPE_DEFAULT) {
                    $diandan_total_convert_number_default = Tools::add($diandan_total_convert_number_default, $orderModel->convert_number, 0);
                } elseif ($orderModel->type === PlaywReportClubProject::TYPE_GIFT) {
                    $diandan_total_number_gift = Tools::add($diandan_total_number_gift, $orderModel->number, 0);
                }

                # # 点单收益
                $diandan_total_z_take_price_all = Tools::add($diandan_total_z_take_price_all, $orderModel->z_take_price_all);
            }
            $club_price_takes_all = Tools::add($club_price_takes_all, $orderModel->club_take_price_all);
        }
        //        $uBossModels = PlaywReportPlaywClubBoss::query()
        //            ->where('club_id', $user->playw_report_club_id)
        //            ->selectRaw('count(*) as total_boss_number');
        //        $uBossModels = $this->addModelTimeWhere($uBossModels, $params, 'join_at');
        //        $uBossModels = $uBossModels->first();

        // 接单量
        $jiedan_total_number_all = Tools::add($jiedan_total_convert_number_default, $jiedan_total_number_gift, 0);

        // 接单额
        $jiedan_total_jiedan_price_all = Tools::add($jiedan_total_jiedan_price_all_default, $jiedan_total_jiedan_price_all_gift);

        // 点单量
        $diandan_total_number_all = Tools::add($diandan_total_convert_number_default, $diandan_total_number_gift);

        $pw_shouyi_all = Tools::add($jiedan_total_price_all, $diandan_total_z_take_price_all);

        $user_order_badges = OrderService::getOrderBadgeByUserIds($userModel->playw_report_club_id, [$userModel->id]);
        return [
            'jiedan_total_number_all' => $jiedan_total_number_all,
            'jiedan_total_convert_number_default' => $jiedan_total_convert_number_default,
            'jiedan_total_number_gift' => $jiedan_total_number_gift,

            'diandan_total_number_all' => $diandan_total_number_all,
            'diandan_total_convert_number_default' => $diandan_total_convert_number_default,
            'diandan_total_number_gift' => $diandan_total_number_gift,

            'jiedan_total_jiedan_price_all' => $jiedan_total_jiedan_price_all,
            'jiedan_total_jiedan_price_all_default' => $jiedan_total_jiedan_price_all_default,
            'jiedan_total_jiedan_price_all_gift' => $jiedan_total_jiedan_price_all_gift,

            'jiedan_total_price_all' => $jiedan_total_price_all,
            'diandan_total_z_take_price_all' => $diandan_total_z_take_price_all,
            'pw_shouyi_all' => $pw_shouyi_all,

            'club_price_takes_all' => $club_price_takes_all,
            # 俱乐部总收益
            'data' => $orderModels->toArray(),

            'user_order_badge' => $user_order_badges[$userModel->id],
            //            'total_boss_number' => $uBossModels->total_boss_number, # 老板数量
        ];
    }

    public function getPageIndexData($userModel, $params, $request)
    {
        return $this->getStatisticsData($userModel, $params, $userModel->id);
    }

    public function getPageMyData($userModel, $params, $request)
    {
        if (isset($params['club_playw_u_ids']) && is_array($params['club_playw_u_ids'])) {
            // var_dump($params['club_playw_u_ids']);
            $data = $this->getStatisticsData($userModel, $params, $params['club_playw_u_ids'][0]);
        } else {
            $data = $this->getStatisticsData($userModel, $params);
        }

        $order_badges = OrderService::getOrderBadge($userModel->playw_report_club_id);
        $data['order_badge'] = $order_badges;

        $apply_badges = ApplyService::getApplyBadge($userModel->playw_report_club_id);
        $apply_badges = $apply_badges->count();
        $data['apply_badge']['un_exec'] = $apply_badges;
        return $data;
    }

    public function getPageStatisticsData($user, $params, $request)
    {
        if (isset($params['club_playw_u_ids']) && is_array($params['club_playw_u_ids'])) {
            $data = $this->getStatisticsData($user, $params, $params['club_playw_u_ids'][0]);
        } else {
            $data = $this->getStatisticsData($user, $params);
        }
        return $data;
    }

    public function postClubAdminAdd($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            if ($user->club->u_id === $params['u_id']) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '不能添加自己');
            }

            $model = User::where('playw_report_club_id', $user->playw_report_club_id)
                ->where('id', '<>', $user->id)
                ->find($params['u_id']);
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }
            if ($model->playw_report_club_admin !== User::PLAYW_REPORT_CLUB_ADMIN_YES) {
                $model->playw_report_club_admin = User::PLAYW_REPORT_CLUB_ADMIN_YES;
                $model->save();
            }

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function deleteClubAdmin($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            if ($user->club->u_id === $params['u_id']) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '不能取消自己');
            }

            $model = User::where('playw_report_club_id', $user->playw_report_club_id)
                ->where('id', '<>', $user->id)
                ->find($params['u_id']);
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }
            if ($model->playw_report_club_admin !== User::PLAYW_REPORT_CLUB_ADMIN_DEFAULT) {
                $model->playw_report_club_admin = User::PLAYW_REPORT_CLUB_ADMIN_DEFAULT;
                $model->save();
            }

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function getClubPageOptions($userModel, $params, $request): array
    {
        Db::beginTransaction();
        try {
            $clubModel = PlaywReportClub::getCacheById($userModel->playw_report_club_id);
            $clubBossList = PlaywReportClub::getBossListSortCreatedAtByClubIdAll($userModel->playw_report_club_id);
            $zIds = array_unique($clubBossList?->pluck('u_id')
                ->toArray());
            $zModels = User::getCacheByIds($zIds);
            foreach ($zModels as $zUserModel) {
                $zUserModel->bosss = User::getBossListSortCreatedAtByClubIdAll($zUserModel->playw_report_club_id, $zUserModel->id);
                $zUserModel->bosss = $zUserModel?->bosss->toArray();
            }
            $zModelsArray = $zModels->toArray();
            // var_dump($zModelsArray);
            $zArray = [];
            foreach ($zModelsArray as $key => &$zModelArray) {
                $zModelArray['value'] = $zModelArray['id'];
                $zModelArray['label'] = $zModelArray['playw_report_playwname'];
                foreach ($zModelArray['bosss'] as &$boss) {
                    $boss['value'] = $boss['id'];
                    $boss['label'] = $zModelArray['playw_report_playwname'] . '/' . $boss['wx_name'];
                    $boss['onshow'] = true;
                    $zModelArray['children'][] = $boss;
                }
                // $zModelArray['children'] = $zModelArray['bosss'];
                unset($zModelArray['bosss']);
                $zArray[] = $zModelArray;
            }

            $models = Db::table((new PlaywReportClubProject())->getTable())
                ->where('club_id', $userModel->playw_report_club_id)
                ->whereIn('type', array_keys(PlaywReportClubProject::getTypeArray()))
                ->orderBy('index', 'desc')
                ->get();

            $defaultArray = $giftArray = [];
            foreach ($models as $model) {
                if ($model->type === PlaywReportClubProject::TYPE_DEFAULT) {
                    $temp = (array) $model;
                    $temp['value'] = $temp['id'];
                    $temp['label'] = $temp['name'];
                    $defaultArray[] = $temp;
                    continue;
                }
                if ($model->type === PlaywReportClubProject::TYPE_GIFT) {
                    $temp = (array) $model;
                    $temp['value'] = $temp['id'];
                    $temp['label'] = $temp['name'];
                    $giftArray[] = $temp;
                }
            }

            $clubUserModels = PlaywReportClub::getUserListSortJoinAtByClubIdAll($userModel->playw_report_club_id);
            $clubUser = [];
            foreach ($clubUserModels as &$item) {
                User::addAttrText($item);
                $clubUser[] = $item;
            }

            $clubProjectModels = PlaywReportClub::getProjectListSortCreatedAtByClubIdAll($userModel->playw_report_club_id);
            $clubProject = [];
            foreach ($clubProjectModels as &$item) {
                PlaywReportClubProject::addAttrText($item);
                $clubProject[] = $item;
            }

            $clubGroupModels = PlaywReportClub::getSortCreatedAtByGroupIdAll($userModel->playw_report_club_id);
            $clubGroup = [];
            foreach ($clubGroupModels as &$item) {
                PlaywReportClubGroup::addAttrText($item);
                $clubGroup[] = $item;
            }

            Db::commit();

            $redis = new Redis();
            $options_order_page_show_zplayw_share_btn = $redis->getOptionsOrderPageShowZplaywShareBtn();
            return [
                'user' => $userModel->toArray(),
                'zArray' => $zArray,
                'club_users' => $clubUser,
                'club_groups_array' => $clubGroup,
                'club_group_method_array' => PlaywReportClubOrder::getClubGroupMethodArray(),
                'club_project_default_array' => $defaultArray,
                'club_project_gift_array' => $giftArray,
                'club_project_convert_array' => Tools::convertModelArrayToJsComponentOptions(PlaywReportClubProject::getConvertArray()),
                'club_project_type_array' => Tools::convertModelArrayToJsComponentOptions(PlaywReportClubProject::getTypeArray()),
                'club_project_price_method_array' => Tools::convertModelArrayToJsComponentOptions(PlaywReportClubProject::getPriceMethodArray()),
                'club_project_club_take_method_array' => Tools::convertModelArrayToJsComponentOptions(PlaywReportClubProject::getClubTakeMethodArray()),
                'club_project_z_take_method_array' => Tools::convertModelArrayToJsComponentOptions(PlaywReportClubProject::getZTakeMethodArray()),
                'club_projects' => $clubProject,
                'club_apply_status_array' => Tools::convertModelArrayToJsComponentOptions(PlaywReportApply::getStatusArray()),
                // 'club_apply_type_array' => Tools::convertModelArrayToJsComponentOptions(PlaywReportApply::getTypeArray()),
                'club_stair_point_rule_type_array' => Tools::convertModelArrayToJsComponentOptions(PlaywReportClubOrderStairPointRule::getTypeArray()),
                'options_order_page_show_zplayw_share_btn' => $options_order_page_show_zplayw_share_btn,
            ];
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function getOptions(): array
    {
        $redis = new Redis();
        $options_force_update_user_privacy_agreement = $redis->getOptionsForceUpdateUserPrivacyAgreement();
        $options_order_page_show_zplayw_share_btn = $redis->getOptionsOrderPageShowZplaywShareBtn();
        return [
            'options_order_page_show_zplayw_share_btn' => $options_order_page_show_zplayw_share_btn,
            'options_force_update_user_privacy_agreement' => $options_force_update_user_privacy_agreement,
        ];
    }

    public function getClubAdminPlaywList($user, $params, $request)
    {
        $models = User::where('playw_report_club_id', $user->playw_report_club_id)
            ->with('clubLeaveApply');
        return $models->paginate((int) $request->input('size', 10));
    }

    public function getClubAdminPlaywListAll($user, $params, $request)
    {
        return PlaywReportClub::getUserListSortJoinAtByClubIdAll($user->playw_report_club_id);
    }

    public function getClubAdminProjectListAll($user, $params, $request)
    {
        return Db::table((new PlaywReportClubProject())->getTable())
            ->where('club_id', $user->playw_report_club_id)
            ->orderBy('index', 'desc')
            ->get();
    }

    public function clubAdminPlaywRemove($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            if (! isset($params['id'])) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }
            $model = User::where('id', $params['id'])
                ->where('playw_report_club_id', $user->playw_report_club_id)
                ->where('id', '<>', $user->id)
                ->first();
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }

            ClubService::playwRemove($model);

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function getClubApplyList($user, $params, $request)
    {
        $result = ApplyService::getApplyList($user->playw_report_club_id, (int) $params['type'], $request);
        $result = $result->toArray();
        $badges = [
            'join_club_count' => 0,
            'leave_club_count' => 0,
            'boss_count' => 0,
        ];

        foreach ($result['data'] as &$item) {
            $item->user = User::getCacheById($item->u_id);
            User::addAttrText($item->user);

            PlaywReportApply::addAttrText($item);
        }

        $apply_badges = ApplyService::getApplyBadge($user->playw_report_club_id);
        foreach ($apply_badges as $apply_badge) {
            switch ($apply_badge->type) {
                case PlaywReportApply::TYPE_CLUB_JOIN:
                    $badges['join_club_count']++;
                    break;
                case PlaywReportApply::TYPE_CLUB_LEAVE:
                    $badges['leave_club_count']++;
                    break;
                case PlaywReportApply::TYPE_BOSS_JOIN:
                    $badges['boss_count']++;
                    break;
                default:
                    break;
            }
        }
        $result['apply_badge_count'] = $badges;
        return $result;
    }

    public function putClubAdminApplyExec($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            if (! isset($params['type'])) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }
            $applyModel = PlaywReportApply::where('id', $params['id'])
                ->where('club_id', $user->playw_report_club_id)
                ->where('status', PlaywReportApply::STATUS_DEFAULT)
                ->first();
            if (! $applyModel) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }
            if ($params['type'] === 'pass') {
                ApplyService::applyPass($applyModel, $user->id);
            } else {
                ApplyService::applyRefuse($applyModel, $user->id);
            }

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function putClubJoinApplyCancel($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            $applyModel = PlaywReportApply::where('id', $params['id'])
                ->where('u_id', $user->id)
                ->where('status', PlaywReportApply::STATUS_DEFAULT)
                ->first();
            if (! $applyModel) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }

            ApplyService::applyRefuse($applyModel, $user->id);

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function getClubAdminGroupList($user, $params, $request)
    {
        return PlaywReportClub::getSortCreatedAtByGroupIdAll($user->playw_report_club_id);
        // return PlaywReportClubGroup::where('club_id', $user->playw_report_club_id)
        //     ->get();
    }

    public function postClubAdminGroup($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            $groupModel = PlaywReportClubGroup::where('club_id', $user->playw_report_club_id)
                ->where('name', $params['group_name'])
                ->first();
            if ($groupModel) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '数据已存在');
            }
            $groupModel = new PlaywReportClubGroup();
            $groupModel->name = $params['group_name'];
            $groupModel->club_id = $user->playw_report_club_id;
            $groupModel->save();

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function putClubAdminGroup($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            $groupModel = PlaywReportClubGroup::where('club_id', $user->playw_report_club_id)
                ->find($params['group_id']);
            if (! $groupModel) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }
            $existsModel = PlaywReportClubGroup::where('club_id', $user->playw_report_club_id)
                ->where('name', $params['group_name'])
                ->where('id', '!=', $params['group_id'])
                ->first();
            if ($existsModel) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '数据已存在');
            }
            $groupModel->name = $params['group_name'];
            $groupModel->save();

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function deleteClubAdminGroup($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            $groupModel = PlaywReportClubGroup::getCacheById($params['group_id']);

            if (! $groupModel || $groupModel->club_id !== $user->playw_report_club_id) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }

            $existsModel = Db::table((new PlaywReportClubOrder())->getTable())
                ->where('club_group_id', $groupModel->id)
                ->exists();
            if ($existsModel) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '已有订单不可操作');
            }

            $groupModel->delete();

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function getClubAdminProject($user, $params, $request)
    {
        return PlaywReportClubProject::where('club_id', $user->playw_report_club_id)
            ->find($params['project_id']);
    }

    public function getClubAdminProjectList($user, $params, $request)
    {
        switch ($params['pageType']) {
            case 'default':
                $type = PlaywReportClubProject::TYPE_DEFAULT;
                break;
            default:
                $type = PlaywReportClubProject::TYPE_GIFT;
                break;
        }
        return Db::table((new PlaywReportClubProject())->getTable())
            ->where('club_id', $user->playw_report_club_id)
            ->where('type', $type)
            ->orderBy('index', 'desc')
            ->get();
    }

    public function postClubAdminProject($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            $model = PlaywReportClubProject::where('name', $params['project_name'])
                ->where('club_id', $user->playw_report_club_id)
                ->where('type', $params['project_type'])
                ->first();
            if ($model) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '数据已存在');
            }
            $model = new PlaywReportClubProject();
            $model->club_id = $user->playw_report_club_id;
            $model->name = $params['project_name'];
            $model->type = $params['project_type'];
            $model->price_method = $params['project_price_method'];
            switch ($params['project_price_method']) {
                case PlaywReportClubProject::PRICE_METHOD_FIXED:
                    $model->price_method_fixed = $params['project_price_method_fixed'];
                    break;
                case PlaywReportClubProject::PRICE_METHOD_DOUBLE:
                    $model->price_method_double = $params['project_price_method_double'];
                    break;
                case PlaywReportClubProject::PRICE_METHOD_PLAYW:
                    break;
                default:
                    throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }

            $model->club_take_method = $params['project_club_take_method'];
            switch ($params['project_club_take_method']) {
                case PlaywReportClubProject::CLUB_TAKE_METHOD_FIXED:
                    $model->club_take_method_fixed = $params['project_club_take_method_fixed'];
                    break;
                case PlaywReportClubProject::CLUB_TAKE_METHOD_RATIO:
                    $model->club_take_method_ratio = $params['project_club_take_method_ratio'];
                    break;
                default:
                    throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }

            $model->z_take_method = $params['project_z_take_method'];
            switch ($params['project_z_take_method']) {
                case PlaywReportClubProject::Z_TAKE_METHOD_FIXED:
                    $model->z_take_method_fixed = $params['project_z_take_method_fixed'];
                    break;
                case PlaywReportClubProject::Z_TAKE_METHOD_RATIO:
                    $model->z_take_method_ratio = $params['project_z_take_method_ratio'];
                    break;
                default:
                    throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }

            $model->convert = $params['project_convert'];
            switch ($params['project_convert']) {
                case PlaywReportClubProject::CONVERT_DEFAULT:
                    break;
                case PlaywReportClubProject::CONVERT_YES:
                    $model->convert_number = $params['project_convert_number'];
                    break;
                default:
                    throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }

            PlaywReportClubProject::checkModelField($model);

            $model->save();

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function putClubAdminProject($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            /**
             * @var PlaywReportClubProject $model
             */
            $model = PlaywReportClubProject::where('club_id', $user->playw_report_club_id)
                ->find($params['project_id']);

            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }
            $model->name = $params['project_name'];
            $model->type = $params['project_type'];
            $model->price_method = $params['project_price_method'];
            $model->price_method_fixed = $params['project_price_method_fixed'];
            $model->price_method_double = $params['project_price_method_double'];
            $model->club_take_method = $params['project_club_take_method'];
            $model->club_take_method_fixed = $params['project_club_take_method_fixed'];
            $model->club_take_method_ratio = $params['project_club_take_method_ratio'];
            $model->z_take_method = $params['project_z_take_method'];
            $model->z_take_method_fixed = $params['project_z_take_method_fixed'];
            $model->z_take_method_ratio = $params['project_z_take_method_ratio'];
            $model->convert = $params['project_convert'];
            $model->convert_number = $params['project_convert_number'];
            $model->index = $params['project_index'];

            PlaywReportClubProject::checkModelField($model);

            $model->save();

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function deleteClubAdminProject($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            $model = PlaywReportClubProject::where('club_id', $user->playw_report_club_id)
                ->find($params['project_id']);
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }
            $existsModel = PlaywReportClubOrder::where('project_id', $model->id)
                ->first();
            if ($existsModel) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '已有订单不可操作');
            }
            $model->delete();

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    /**
     * 获取俱乐部设置，只能获取本俱乐部的设置.
     * @param mixed $user
     * @param mixed $params
     * @param mixed $request
     * @return true
     * @throws Throwable
     */
    public function getClubAdminSetting($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            $model = PlaywReportClub::where('id', $user->playw_report_club_id)
                ->first();
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }

            Db::commit();
            return $model->toArray();
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    /**
     * 更新俱乐部设置，只能更新本俱乐部的设置.
     * @param mixed $user
     * @param mixed $params
     * @param mixed $request
     * @return array
     * @throws Throwable
     */
    public function putClubAdminSetting($user, $params, $request)
    {
        Db::beginTransaction();
        try {
            $model = PlaywReportClub::where('id', $user->playw_report_club_id)
                ->first();
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }

            if (! $this->checkParams($params)) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }
            $model->name = $params['name'];
            $model->auto_apply_boss_create = $params['auto_apply_boss_create'];
            $model->auto_apply_club_join = $params['auto_apply_club_join'];
            $model->auto_apply_club_leave = $params['auto_apply_club_leave'];
            $model->save();
            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    /**
     * 检查参数.
     * @param mixed $params
     */
    private function checkParams($params): bool
    {
        if (! isset($params['name']) || ! $params['name']) {
            return false;
        }
        // 状态值 要匹配模型中的array的key
        if (! isset($params['auto_apply_boss_create']) || ! in_array($params['auto_apply_boss_create'], array_keys(PlaywReportClub::getAutoApplyBossArray()))) {
            return false;
        }
        if (! isset($params['auto_apply_club_join']) || ! in_array($params['auto_apply_club_join'], array_keys(PlaywReportClub::getAutoApplyClubJoinArray()))) {
            return false;
        }
        if (! isset($params['auto_apply_club_leave']) || ! in_array($params['auto_apply_club_leave'], array_keys(PlaywReportClub::getAutoApplyClubLeaveArray()))) {
            return false;
        }
        return true;
    }
}
