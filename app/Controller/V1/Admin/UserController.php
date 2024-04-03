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

namespace App\Controller\V1\Admin;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use App\Exception\RetException;
use App\Exception\ServiceException;
use App\Model\SysDept;
use App\Model\SysRole;
use App\Model\SysUser;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Annotation\AutoController;

/**
 * Class UserController.
 * @AutoController
 */
class UserController extends AbstractController
{
    public function updatePass()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $token = $this->request->header('Authorization');
        if (! isset($token, $params['oldPass'])) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '请填写旧密码');
        }

        # 不能重复 title、component_name(name)
        $model = SysUser::where('token', $token)
            ->where('password', $params['oldPass'])
            ->first();
        if (! $model) {
            # 密码错误
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '旧密码错误');
        }
        $model->password = $params['newPass'];
        $model->save();
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function updateEmail()
    {
        // $params = $this->getRequestAllFilter();
        // // var_dump($params);
        // $token = $this->request->header('Authorization');
        // if (! isset($token, $params['oldPass'])) {
        //     return $this->responseJson(ServiceCode::ERROR_PARAM_MISSING);
        // }
        //
        // # 不能重复 title、component_name(name)
        // $model = SysUser::where('token', $token)
        //     ->first();
        // if (! $model) {
        //     throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        // }
        // $model->email = $params['email'];
        // $model->save();
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function exist()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        $model = SysUser::where('username', $params['username'])
            ->count();
        var_dump($model);
        if ($model > 1) {
            return $this->responseJson(ServiceCode::SUCCESS, ['exist' => true]);
        }
        return $this->responseJson(ServiceCode::SUCCESS, ['exist' => false]);
    }
}
