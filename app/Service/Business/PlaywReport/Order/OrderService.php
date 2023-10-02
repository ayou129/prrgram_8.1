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

namespace App\Service\Business\PlaywReport\Order;

use App\Constant\ServiceCode;
use App\Exception\ServiceException;
use App\Model\PlaywReportClubGroup;
use App\Model\PlaywReportClubOrder;
use App\Model\PlaywReportClubProject;
use App\Model\PlaywReportPlaywClubBoss;
use App\Model\User;
use App\Service\Business\PlaywReport\CommonService;
use App\Service\Business\PlaywReport\Project\ProjectManager;
use App\Service\Business\PlaywReport\StairPoint\StairPointService;
use App\Utils\Tools;
use Hyperf\DbConnection\Db;
use Throwable;

class OrderService extends CommonService implements OrderInterface
{
    public function addModelWhere($models, $params, $userModel, $admin = false)
    {
        if (! $admin) {
            // 非管理员，只能看到自己的订单（u_id,z_u_id）
            if (! isset($params['formTypeSearchMode'])) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '时间参数错误');
            }
            // 区分接单、点单
            switch ($params['formTypeSearchMode']) {
                case '1':
                    $models = $models->where('u_id', $userModel->id);
                    break;
                case '2':
                    $models = $models->where('z_u_id', $userModel->id);
                    break;
                default:
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '时间参数错误');
            }
        } else {
            // 管理员，可以搜索任何人,如果有条件，则根据条件筛选
            if (isset($params['club_playw_u_ids']) && is_array($params['club_playw_u_ids'])) {
                // 接单陪玩
                $models = $models->whereIn('u_id', $params['club_playw_u_ids']);
            }
        }
        if (isset($params['club_zplayw_u_ids']) && is_array($params['club_zplayw_u_ids'])) {
            // 直属
            $models = $models->whereIn('z_u_id', $params['club_zplayw_u_ids']);
        }
        if (isset($params['club_boss_ids']) && is_array($params['club_boss_ids'])) {
            // 老板
            $models = $models->whereIn('club_boss_id', $params['club_boss_ids']);
        }
        if (isset($params['club_order_type_value']) && $params['club_order_type_value']) {
            // 订单类型
            $models = $models->where('type', $params['club_order_type_value']);
        }

        return $models;
    }

    public function getOrderById($userModel, $params, $request, $admin = false)
    {
        $model = PlaywReportClubOrder::getCacheById($userModel->playw_report_club_id);

        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据不存在');
        }

        $model->user = User::getCacheById($model->u_id);
        $model->zUser = User::getCacheById($model->z_u_id);
        $model->project = PlaywReportClubProject::getCacheById($model->project_id);
        $model->boss = PlaywReportPlaywClubBoss::getCacheById($model->club_boss_id);

        return $model->toArray();
    }

    // 获取订单列表
    public function orderList($userModel, $params, $request, $admin = false)
    {
        $models = Db::table((new PlaywReportClubOrder())->getTable())
            ->where('club_id', $userModel->playw_report_club_id);

        $models = $this->addModelTimeWhere($models, $params);

        $models = $this->addModelWhere($models, $params, $userModel, $admin);

        $where = $whereOr = [];
        $models = $models->where($where)
            ->where(function ($query) use ($whereOr) {
                foreach ($whereOr as $item) {
                    $query->where(...$item[0])
                        ->orWhere(...$item[1]);
                }
            })
            ->orderBy('id', 'desc');

        $result = $models->paginate((int) $request->input('size', 10))
            ->toArray();
        foreach ($result['data'] as &$item) {
            $user = User::getCacheById($item->u_id);
            User::addAttrText($user);
            $item->user = $user;
            $zUser = User::getCacheById($item->z_u_id);
            User::addAttrText($zUser);
            $item->zUser = $zUser;

            $item->project = PlaywReportClubProject::getCacheById($item->project_id);
            $item->boss = PlaywReportPlaywClubBoss::getCacheById($item->club_boss_id);
            PlaywReportClubOrder::addAttrText($item);
        }
        $user_order_badges = OrderService::getOrderBadgeByUserIds($userModel->playw_report_club_id, [$userModel->id]);
        $result['user_order_badges'] = $user_order_badges[$userModel->id];
        return $result;
    }

    public static function getOrderBadgeByUserIds(int $club_id, array $user_ids)
    {
        $orderModels = Db::table((new PlaywReportClubOrder())->getTable())
            ->where('club_id', $club_id)
            ->where(function ($query) {
                $query->where('fd_status', PlaywReportClubOrder::FD_STATUS_DEFAULT)
                    ->orWhere('jq_status', PlaywReportClubOrder::JQ_STATUS_DEFAULT);
            })
            ->where(function ($query) use ($user_ids) {
                $query->whereIn('u_id', $user_ids)
                    ->orWhereIn('z_u_id', $user_ids);
            });

        $orderModels = $orderModels->get();

        $result = [];
        foreach ($user_ids as $id) {
            $result[$id] = [
                'jiedan_un_jq_status_count' => 0,
                'jiedan_un_fd_status_count' => 0,
                'jiedan_count_total' => 0,
                'diandan_un_jq_status_count' => 0,
                'diandan_un_fd_status_count' => 0,
                'diandan_count_total' => 0,
                'count_total' => 0,
            ];
        }

        foreach ($orderModels as $key => $orderModel) {
            foreach ($user_ids as $user_id) {
                if ($orderModel->u_id === $user_id) {
                    if ($orderModel->jq_status === PlaywReportClubOrder::JQ_STATUS_DEFAULT) {
                        ++$result[$user_id]['jiedan_un_jq_status_count'];
                    }
                    if ($orderModel->fd_status === PlaywReportClubOrder::FD_STATUS_DEFAULT) {
                        ++$result[$user_id]['jiedan_un_fd_status_count'];
                    }
                }
                if ($orderModel->z_u_id === $user_id) {
                    if ($orderModel->jq_status === PlaywReportClubOrder::JQ_STATUS_DEFAULT) {
                        ++$result[$user_id]['diandan_un_jq_status_count'];
                    }
                    if ($orderModel->fd_status === PlaywReportClubOrder::FD_STATUS_DEFAULT) {
                        ++$result[$user_id]['diandan_un_fd_status_count'];
                    }
                }
            }
        }
        //        var_dump($user_ids, $result);

        foreach ($result as $key => &$item) {
            $item['jiedan_count_total'] = Tools::add($item['jiedan_un_jq_status_count'], $item['jiedan_un_fd_status_count'], 0);
            $item['diandan_count_total'] = Tools::add($item['diandan_un_jq_status_count'], $item['diandan_un_fd_status_count'], 0);

            $item['count_total'] = Tools::add($item['jiedan_count_total'], $item['diandan_count_total'], 0);

            # 额外
            # # index page
            $item['index_page_count_total'] = Tools::add($item['jiedan_un_jq_status_count'], $item['diandan_un_jq_status_count'], 0);
            # # my index
            $item['my_page_count_total'] = Tools::add($item['jiedan_un_fd_status_count'], $item['diandan_un_fd_status_count'], 0);
        }
        //            var_dump($user_ids, $result);
        //        var_dump($user_ids);

        return $result;
    }

    public static function getOrderBadge(int $club_id)
    {
        $orderModels = PlaywReportClubOrder::where('club_id', $club_id)
            ->where(function ($query) {
                $query->where('fd_status', PlaywReportClubOrder::FD_STATUS_DEFAULT)
                    ->orWhere('jq_status', PlaywReportClubOrder::JQ_STATUS_DEFAULT);
            });
        $orderModels = $orderModels->get();

        $result = [
            'un_jq_status_count_all_user' => 0,
            'un_fd_status_count_all_user' => 0,
        ];
        foreach ($orderModels as $key => $orderModel) {
            if ($orderModel->jq_status === PlaywReportClubOrder::JQ_STATUS_DEFAULT) {
                ++$result['un_jq_status_count_all_user'];
            }
            if ($orderModel->fd_status === PlaywReportClubOrder::FD_STATUS_DEFAULT) {
                ++$result['un_fd_status_count_all_user'];
            }
        }
        return $result;
    }

    public function postAndPutModel($model, $userModel, $params)
    {
        // 判断 club_group_method，它是一个数组
        if (! isset($params['club_group_method']) || ! in_array($params['club_group_method'], array_keys(PlaywReportClubOrder::getClubGroupMethodArray()))) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '请选择群类型');
        }

        $projectModel = PlaywReportClubProject::query()
            ->where('club_id', $userModel->playw_report_club_id)
            ->where('id', $params['project_id'])
            ->first();
        if (! $projectModel) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '项目不存在');
        }
        if ($projectModel->type === PlaywReportClubProject::TYPE_DEFAULT && ! isset($params['end_at'])) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '请填写结束时间');
        }

        // 通过计算金额等复用方法获取订单信息，并且创建订单
        $model->club_id = $userModel->playw_report_club_id;
        $model->u_id = $userModel->id;
        $model->project_id = $projectModel->id;
        $model->project_name = $projectModel->name;
        if ($params['club_group_method'] == PlaywReportClubOrder::CLUB_GROUP_METHOD_DEFAULT) {
            $groupModel = PlaywReportClubGroup::query()
                ->where('club_id', $userModel->playw_report_club_id)
                ->where('id', $params['group_id'])
                ->first();
            if (! $groupModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '分组不存在');
            }
            $model->club_group_id = $groupModel->id;
            $model->club_group_name = $groupModel->name;

            if (! isset($params['playw_is_boss'])) {
                $params['playw_is_boss'] = true;
            }
            if ($params['playw_is_boss']) {
                // 是老板，则只需要提供直属ID
                $zPlaywUser = User::where('playw_report_club_id', $userModel->playw_report_club_id)
                    ->find($params['zplayw_id']);
                if (! $zPlaywUser) {
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '直属不存在');
                }
                $model->z_u_id = $zPlaywUser->id;
            } else {
                // 不是老板，则只需要提供老板ID，查询出直属ID
                $playwBossModel = PlaywReportPlaywClubBoss::query()
                    ->where('club_id', $userModel->playw_report_club_id)
                    ->where('id', $params['boss_id'])
                    ->first();
                if (! $playwBossModel) {
                    throw new ServiceException(ServiceCode::ERROR, [], 400, [], '老板不存在');
                }
                $model->club_boss_id = $playwBossModel->id;
                $model->club_boss_wx_name = $playwBossModel->wx_name;
                $model->club_boss_wx_number = $playwBossModel->wx_number;
                $model->z_u_id = $playwBossModel->u_id;
            }
        } else {
            # 群外要选择陪玩
            $zPlaywUser = User::where('playw_report_club_id', $userModel->playw_report_club_id)
                ->find($params['zplayw_id']);
            if (! $zPlaywUser) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '直属不存在');
            }
            $model->z_u_id = $zPlaywUser->id;
        }
        $model->club_group_method = $params['club_group_method'];
        $model->start_at = $params['start_at'];
        if (isset($params['end_at'])) {
            if ($params['end_at'] < $params['start_at']) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '时间有误');
            }
            $model->end_at = $params['end_at'];
        }
        $model->type = $projectModel->type;
        $model->number = $params['number'];

        // 货币相关
        $orderData = self::getOrderData($projectModel, $userModel, $params);
        $model->pw_danjia_price = $orderData['pw_danjia_price'];
        $model->jiedan_price = $orderData['jiedan_price'];
        $model->jiedan_price_all = $orderData['jiedan_price_all'];
        $model->convert_number = $orderData['convert_number'];
        $model->club_take_price = $orderData['club_take_price'];
        $model->club_take_price_all = $orderData['club_take_price_all'];
        $model->z_take_stair_point_discount_price = $orderData['z_take_stair_point_discount_price'];
        $model->z_take_price = $orderData['z_take_price'];
        $model->z_take_price_all = $orderData['z_take_price_all'];
        $model->price = $orderData['price'];
        $model->price_all = $orderData['price_all'];

        $model->status = PlaywReportClubOrder::STATUS_DEFAULT;
        return $model;
    }

    // 修改订单
    public function putOrder($userModel, $params, $request): bool
    {
        Db::beginTransaction();
        try {
            if (! $params['id']) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '参数不存在');
            }
            $model = PlaywReportClubOrder::query()
                ->where('club_id', $userModel->playw_report_club_id)
                ->where('u_id', $userModel->id)
                ->find($params['id']);
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR);
            }
            if ($model->fd_status == PlaywReportClubOrder::FD_STATUS_FINISHED) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '返点已结，无法操作');
            }
            if ($model->jq_status == PlaywReportClubOrder::JQ_STATUS_YES) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '陪玩已结，无法操作');
            }

            $model = $this->postAndPutModel($model, $userModel, $params);

            $model->save();
            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    // 修改订单
    public function deleteOrder($userModel, $params, $request): bool
    {
        Db::beginTransaction();
        try {
            if (! $params['id']) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '参数不存在');
            }
            $model = PlaywReportClubOrder::query()
                ->where('club_id', $userModel->playw_report_club_id)
                ->where('u_id', $userModel->id)
                ->find($params['id']);
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR);
            }
            if ($model->fd_status == PlaywReportClubOrder::FD_STATUS_FINISHED) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '返点已结，无法操作');
            }
            if ($model->jq_status == PlaywReportClubOrder::JQ_STATUS_YES) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '陪玩已结，无法操作');
            }

            $model->delete();
            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function orderCreate($userModel, $params, $request): bool
    {
        Db::beginTransaction();
        try {
            $model = new PlaywReportClubOrder();
            $model = $this->postAndPutModel($model, $userModel, $params);
            $model->save();

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function checkUserPlaywReportClubJiedanPrice($userModel, $exception = true)
    {
        if (! $exception) {
            return $userModel->playw_report_club_jiedan_price ? true : false;
        }
        if (! $userModel->playw_report_club_jiedan_price) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '请先设置接单价格');
        }
    }

    public function orderCalculate($userModel, $params, $request)
    {
        $projectModel = PlaywReportClubProject::query()
            ->where('club_id', $userModel->playw_report_club_id)
            ->where('id', $params['project_id'])
            ->first();
        if (! $projectModel) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '项目不存在');
        }

        return self::getOrderData($projectModel, $userModel, $params);
    }

    public function putOrderJqJiezhang($userModel, $params, $request)
    {
        Db::beginTransaction();
        try {
            $orderModel = PlaywReportClubOrder::query()
                ->where('club_id', $userModel->playw_report_club_id)
                ->where('z_u_id', $userModel->id)
                ->where('id', $params['id'])
                ->first();
            if (! $orderModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据不存在');
            }
            if ($orderModel->jq_status === PlaywReportClubOrder::JQ_STATUS_DEFAULT) {
                $orderModel->jq_status = PlaywReportClubOrder::JQ_STATUS_YES;
                $orderModel->save();
            }

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    public function putOrderJqFandian($userModel, $params, $request)
    {
        Db::beginTransaction();
        try {
            $orderModel = PlaywReportClubOrder::query()
                ->where('club_id', $userModel->playw_report_club_id)
                ->where('id', $params['id'])
                ->first();
            if (! $orderModel) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据不存在');
            }
            if ($orderModel->fd_status === PlaywReportClubOrder::FD_STATUS_DEFAULT) {
                $orderModel->fd_status = PlaywReportClubOrder::FD_STATUS_FINISHED;
                $orderModel->save();
            }

            Db::commit();
            return true;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    // 通过计算金额等复用方法获取订单信息
    private static function getOrderData($projectModel, $userModel, $params)
    {
        $projectManager = new ProjectManager($projectModel, $userModel);

        $jiedan_price = $projectManager->getPrice();
        // var_dump('$jiedan_price', $jiedan_price);

        $convert_number = Tools::mul($projectManager->getConvertNumber(), $params['number']);
        // var_dump('$convert_number', $convert_number);

        $jiedan_price_all = Tools::mul($projectManager->getPrice(), $params['number']);
        // var_dump('$jiedan_price_all', $jiedan_price_all);

        $club_take_price = $projectManager->getClubTakePrice();
        // var_dump('$club_take_price', $club_take_price);

        $club_take_price_all = Tools::mul($club_take_price, $params['number']);
        // var_dump('$club_take_price_all', $club_take_price_all);

        //        $stairPointDisCountDetails = StairPointService::getDisCountDetails($userModel, $projectModel, $jiedan_price);
        $stairPointDisCountDetails = false;
        $z_take_stair_point_discount_price = 0;
        if ($stairPointDisCountDetails) {
            $z_take_stair_point_discount_price = $stairPointDisCountDetails['discount_price'];
        }
        $z_take_price = $projectManager->getZTakePrice();
        $z_take_price = Tools::add($z_take_price, $z_take_stair_point_discount_price);
        // var_dump('$z_take_price', $z_take_price);

        $z_take_price_all = Tools::mul($z_take_price, $params['number']);
        // var_dump('$z_take_price_all', $z_take_price_all);

        $price = $projectManager->getPrice();

        // 收益=接单价-俱乐部抽成-直属返点
        $price = Tools::sub($jiedan_price, $club_take_price);
        $price = Tools::sub($price, $z_take_price);
        // var_dump('$price', $price);

        // 总收益=总接单价-总俱乐部抽成-总直属返点 换句话说 总收益=每单收益*单数
        $price_all = Tools::mul($price, $params['number']);

        return [
            'convert_number' => $convert_number,
            'pw_danjia_price' => $userModel->playw_report_club_jiedan_price,
            'jiedan_price' => $jiedan_price,
            'jiedan_price_all' => $jiedan_price_all,
            'club_take_price' => $club_take_price,
            'club_take_price_all' => $club_take_price_all,
            'z_take_stair_point_discount_price' => $z_take_stair_point_discount_price,
            'z_take_price' => $z_take_price,
            'z_take_price_all' => $z_take_price_all,
            'price' => $price,
            'price_all' => $price_all,
        ];
    }
}
