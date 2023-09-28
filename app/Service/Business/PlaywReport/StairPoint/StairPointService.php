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

namespace App\Service\Business\PlaywReport\StairPoint;

use App\Constant\ServiceCode;
use App\Exception\ServiceException;
use App\Model\PlaywReportClubOrderStairPoint;
use App\Model\PlaywReportClubOrderStairPointExcludeProject;
use App\Model\PlaywReportClubOrderStairPointExcludeUser;
use App\Model\PlaywReportClubOrderStairPointGenAssociation;
use App\Model\PlaywReportClubOrderStairPointRule;
use App\Model\PlaywReportClubProject;
use App\Model\User;
use Hyperf\DbConnection\Db;
use Throwable;

class StairPointService
{
    /**
     * 检查参数.
     * @param mixed $params
     */
    public static function checkParams($params): bool
    {
        // 检查params
        if (! isset($params['status']) || ! in_array($params['status'], array_keys(PlaywReportClubOrderStairPoint::getStatusArray()))) {
            return false;
        }

        if ($params['status']) {
            return true;
        }

        if (! isset($params['rule']) || ! is_array($params['rule'])) {
            return false;
        }

        foreach ($params['rule'] as $key => $value) {
            if (isset($rule['from_amount'], $rule['to_amount'], $rule['point_method_fixed'], $rule['point_method_ratio'])) {
                return false; // 缺少必要参数
            }
        }

        if ($params['exclude_project_ids'] && ! is_array($params['exclude_project_ids'])) {
            return false;
        }

        if ($params['exclude_user_ids'] && ! is_array($params['exclude_user_ids'])) {
            return false;
        }

        return true;
    }

    /**
     * 获取俱乐部的阶梯返点数据.
     *
     * @param mixed $userModel
     * @param mixed $params
     * @param mixed $request
     */
    public static function getClubAdminStairPoints($userModel, $params, $request)
    {
        return PlaywReportClubOrderStairPoint::where('club_id', $userModel->playw_report_club_id)
            ->with([
                'rule',
                'excludeUsers' => function ($query) {
                    $query->with('user');
                },
                'excludeProjects' => function ($query) {
                    $query->with('project');
                },
            ])
            ->first();
    }

    /**
     * 更新俱乐部的阶梯返点数据.
     *
     * @param mixed $userModel
     * @param mixed $params
     * @param mixed $request
     */
    public static function putClubAdminStairPoint($userModel, $params, $request)
    {
        Db::beginTransaction();

        try {
            $model = PlaywReportClubOrderStairPoint::where('club_id', $userModel->playw_report_club_id)
                ->first();
            if (! $model) {
                $model = new PlaywReportClubOrderStairPoint();
                $model->club_id = $userModel->playw_report_club_id;
                $model->status = PlaywReportClubOrderStairPoint::STATUS_DEFAULT;
                $model->save();
            }

            if (! self::checkParams($params)) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '参数有误');
            }

            $err_msg = self::checkRule($params['rule']);
            if (is_string($err_msg)) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], $err_msg);
            }

            if ($params['status']) {
                // 循环插入stair_point_rule表
                PlaywReportClubOrderStairPointRule::where('club_id', $userModel->playw_report_club_id)
                    ->where('stair_point_id', $model->id)
                    ->delete();
                $saveModels = [];
                foreach ($params['rule'] as $rule) {
                    // var_dump($rule);
                    $saveModels[] = [
                        'club_id' => $userModel->playw_report_club_id,
                        'stair_point_id' => $model->id,
                        'from_amount' => $rule['from_amount'],
                        'to_amount' => $rule['to_amount'],
                        'type' => $rule['type'],
                        'point_method_fixed' => $rule['point_method_fixed'],
                        'point_method_ratio' => $rule['point_method_ratio'],
                    ];
                }
                PlaywReportClubOrderStairPointRule::insert($saveModels);

                if ($params['exclude_project_ids']) {
                    $projectModels = PlaywReportClubProject::where('club_id', $userModel->playw_report_club_id)
                        ->whereIn('id', $params['exclude_project_ids'])
                        ->get();
                    if ($projectModels->count() != count($params['exclude_project_ids'])) {
                        throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], 'exclude_project_ids参数有误');
                    }
                    // 插入关联表
                    $projectIds = $projectModels->pluck('id')
                        ->toArray();
                    $projectModels = PlaywReportClubOrderStairPointExcludeProject::where('club_id', $userModel->playw_report_club_id)
                        ->where('stair_point_id', $model->id)
                        ->delete();
                    $saveModels = [];
                    foreach ($projectIds as $projectId) {
                        $saveModels[] = [
                            'club_id' => $userModel->playw_report_club_id,
                            'project_id' => $projectId,
                            'stair_point_id' => $model->id,
                        ];
                    }
                    PlaywReportClubOrderStairPointExcludeProject::insert($saveModels);
                }
                if ($params['exclude_user_ids']) {
                    $userModels = User::where('playw_report_club_id', $userModel->playw_report_club_id)
                        ->whereIn('id', $params['exclude_user_ids'])
                        ->get();
                    if ($userModels->count() != count($params['exclude_user_ids'])) {
                        throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], 'exclude_user_ids参数有误');
                    }
                    // 插入关联表
                    $userIds = $params['exclude_user_ids'];
                    PlaywReportClubOrderStairPointExcludeUser::where('club_id', $userModel->playw_report_club_id)
                        ->where('stair_point_id', $model->id)
                        ->delete();
                    $saveModels = [];
                    foreach ($userIds as $userId) {
                        $saveModels[] = [
                            'club_id' => $userModel->playw_report_club_id,
                            'u_id' => $userId,
                            'stair_point_id' => $model->id,
                        ];
                    }
                    PlaywReportClubOrderStairPointExcludeUser::insert($saveModels);
                }
            }

            $model->status = $params['status'];
            $model->save();

            Db::commit();

            return true;
        } catch (Throwable $ex) {
            Db::rollBack();

            throw $ex;
        }
    }

    /**
     * 检查规则参数是否正确.
     *
     * @param mixed $rules
     */
    public static function checkRule($rules)
    {
        // 用于存储每个类型下的规则，以type为键，对应规则数组为值
        $typeRulesMap = [];
        // 按照type对规则进行分类
        foreach ($rules as $rule) {
            $type = $rule['type'];
            if (! isset($typeRulesMap[$type])) {
                $typeRulesMap[$type] = [];
            }
            $typeRulesMap[$type][] = $rule;
        }

        // 对每个类型下的规则按from_amount进行排序
        foreach ($typeRulesMap as $type => $typeRules) {
            $temp = $typeRules;
            usort($temp, function ($a, $b) {
                return $a['from_amount'] - $b['from_amount'];
            });
            $typeRulesMap[$type] = $temp;
        }

        // 对每个类型下的规则进行区间检查
        foreach ($typeRulesMap as $type => $typeRules) {
            $minValue = null;
            $maxValue = null;
            foreach ($typeRules as $rule) {
                $fromAmount = $rule['from_amount'];
                $toAmount = $rule['to_amount'];

                if ($fromAmount >= $toAmount) {
                    return '规则区间无效';
                }

                if ($minValue !== null && ($fromAmount <= $minValue || $fromAmount <= $maxValue)) {
                    return '存在覆盖';
                }

                // 更新当前类型的最小值和最大值
                $minValue = ($minValue === null) ? $fromAmount : min($minValue, $fromAmount);
                $maxValue = ($maxValue === null) ? $toAmount : max($maxValue, $toAmount);
            }
        }

        return true;
    }

    /**
     * 外部定时器，定时生成对应的PlaywReportClubOrderStairPointGenAssociation数据，每次生成都会清空之前的数据.
     * @param mixed $userModel
     * @param mixed $params
     * @param mixed $request
     */
    public static function genAssociation($userModel, $params, $request): bool
    {
        $stairPointModel = PlaywReportClubOrderStairPoint::where('club_id', $userModel->playw_report_club_id)
            ->with([
                'rule',
                'excludeUsers' => function ($query) {
                    $query->with('user');
                },
                'excludeProjects' => function ($query) {
                    $query->with('project');
                },
            ])
            ->first();
        if (! $stairPointModel) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '未开启功能');
        }
        // 状态未开启，报错
        if ($stairPointModel->status != PlaywReportClubOrderStairPoint::STATUS_YES) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '未开启功能');
        }
        if (! $stairPointModel->rule) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], '未设置规则');
        }

        $err_msg = self::checkRule($stairPointModel->rule);
        if (is_string($err_msg)) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT, [], 400, [], $err_msg);
        }

        PlaywReportClubOrderStairPointGenAssociation::where('club_id', $userModel->playw_report_club_id)
            ->where('stair_point_id', $stairPointModel->id)
            ->delete();

        $userModels = User::where('playw_report_club_id', $userModel->playw_report_club_id)
            ->get();
        if (! $userModels) {
            return true;
        }
        $userModels = $userModels->toArray();
        $userModels = array_column($userModels, null, 'id');
        $userIds = array_keys($userModels);
        $saveModels = [];
        foreach ($userIds as $userId);
        //            foreach ($stairPointRuleIds as $stairPointRuleId) {
        //                $saveModels[] = [
        //                    'club_id' => $userModel->playw_report_club_id,
        //                    'stair_point_id' => $stairPointModel->id,
        //                    'stair_point_rule_id' => $stairPointRuleId,
        //                    'user_id' => $userId,
        //                    'status' => PlaywReportClubOrderStairPointGenAssociation::STATUS_DEFAULT,
        //                ];
        //            }
    }

    // 从 $StairPointModel 中获取阶梯返点配置也就是->rule,根据rule中的type，获取对应的阶梯返点配置
    // 触发点：在执行定时任务中，先某俱乐部的阶梯返点配置，判断是否开启；如果开启，获取阶梯返点配置，然后根据阶梯返点配置
    // 获取本俱乐部的所有成员，然后根据成员的部分数据，传入该方法中，判断是否符合阶梯返点条件，如果符合，就生成阶梯返点
    public static function getStairPointConfig($stairPointModel, $userIndexAndData)
    {
        if (! $stairPointModel->rule) {
            return false;
        }
        $rule = $stairPointModel->rule;
        $excludeUsers = $stairPointModel->excludeUsers;
        $excludeProjects = $stairPointModel->excludeProjects;
        // 判断用户是否被排除
        if ($excludeUsers) {
            foreach ($excludeUsers as $excludeUser) {
                if ($excludeUser->user_id == $userIndexAndData['user_id']) {
                    return false;
                }
            }
        }

        foreach ($clubUserModels as $clubUserModel) {
            $type = $rule['type'];
            $fromAmount = $rule['from_amount'];
            $toAmount = $rule['to_amount'];
            $pointMethodFixed = $rule['point_method_fixed'];
            $pointMethodRatio = $rule['point_method_ratio'];

            // 判断是否符合阶梯返点条件
            if (! self::checkStairPointGenAssociation($userModel, $projectModel)) {
                return false;
            }

            // 生成阶梯返点
            $stairPoint = new PlaywReportClubOrderStairPoint();
            $stairPoint->club_id = $userModel->playw_report_club_id;
            $stairPoint->u_id = $userModel->id;
            $stairPoint->project_id = $projectModel->id;
            $stairPoint->type = $type;
            $stairPoint->from_amount = $fromAmount;
            $stairPoint->to_amount = $toAmount;
            $stairPoint->point_method_fixed = $pointMethodFixed;
            $stairPoint->point_method_ratio = $pointMethodRatio;
            $stairPoint->save();
        }

        return true;
    }

    public function deleteStairPoint($id)
    {
        $stairPoint = PlaywReportClubOrderStairPoint::find($id);

        return $stairPoint->delete();
    }

    // // 获取排除的用户Ids
    // public static function getExcludeUserIds(PlaywReportClubOrderStairPoint $model)
    // {
    //     return $model->excludeUserIds ? $model->excludeUserIds->toArray() : [];
    // }

    // // 获取排除的所有项目Ids
    // public static function getExcludeProjectIds(PlaywReportClubOrderStairPoint $model)
    // {
    //     return $model->excludeProjectIds ? $model->excludeProjectIds->toArray() : [];
    // }

    // 检查是否符合阶梯返点条件
    public static function checkStairPointGenAssociation($userModel, $projectModel)
    {
        $stairPointGenAssociationModel = PlaywReportClubOrderStairPointGenAssociation::where('club_id', $userModel->playw_report_club_id)
            ->where('project_id', $projectModel->id)
            ->where('u_id', $userModel->id)
            ->with([
                'stairPoint' => function ($query) {
                    $query->with('excludeUserIds', 'excludeProjectIds');
                },
            ])
            ->first();
        if (! $stairPointGenAssociationModel
            || ! $stairPointGenAssociationModel->stairPoint
            || ! $stairPointGenAssociationModel->stairPoint->excludeUserIds
            || ! $stairPointGenAssociationModel->stairPoint->excludeProjectIds) {
            return false;
        }
        $excludeUserIds = $stairPointGenAssociationModel->stairPoint->excludeUserIds->pluck('u_id');
        if (in_array($userModel->id, $excludeUserIds)) {
            return false;
        }

        $excludeProjectIds = $stairPointGenAssociationModel->stairPoint->excludeProjectIds->pluck('project_id');
        if (in_array($projectModel->id, $excludeProjectIds)) {
            return false;
        }
        if (! $stairPointGenAssociationModel->stairPoint) {
            return false;
        }

        return $stairPointGenAssociationModel;
    }

    /**
     * 检查状态
     * @param mixed $stairPointModel
     * @param mixed $e
     */
    public static function checkStatus($stairPointModel, $e = false): bool
    {
        $status = $stairPointModel->status == PlaywReportClubOrderStairPoint::STATUS_DEFAULT;
        if ($e && ! $status) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
        }
        return $status;
    }

    /**
     * @param mixed $userModel
     * @param mixed $projectModel
     * @param mixed $price
     * @return array|false
     */
    public static function getDisCountDetails($userModel, $projectModel, $price): array
    {
        if (! $userModel->club->stairPoint) {
            return false;
        }
        if (! self::checkStatus($userModel->club->stairPoint)) {
            return false;
        }
        $strategy = new StairPointDiscountDetailsStrategy($userModel->club->stairPoint, ...func_get_args());
        return $strategy->getDiscountDetails();
    }
}
