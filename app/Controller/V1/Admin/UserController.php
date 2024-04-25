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
use App\Model\SysUser;
use App\Model\User;
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
        if (! isset($token, $params['old_password'])) {
            throw new RetException('please fill in the old password');
        }

        $model = SysUser::where('token', $token)
            ->where('password', $params['old_password'])
            ->first();
        if (! $model) {
            # 密码错误
            throw new RetException('old password error');
        }
        $model->password = $params['new_password'];
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

    public function list()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $limit = (int) $this->request->input('page_limit', 10);
        $where = $whereOr = [];

        if (isset($params['blurry'])) {
            $whereOr[] = [
                [
                    'username',
                    'like',
                    '%' . $params['blurry'] . '%',
                ],
                [
                    'email',
                    'like',
                    '%' . $params['blurry'] . '%',
                ],
            ];
        }

        $models = User::where($where)
            ->where(function ($query) use ($whereOr) {
                foreach ($whereOr as $item) {
                    $query->where(...$item[0])
                        ->orWhere(...$item[1]);
                }
            })
            ->with([
            ]);

        $params['sort'] = $this->request->input('sort') ?? [];
        foreach ($params['sort'] as $item) {
            $sort = explode(',', $item);
            $sort_field = $sort[0];
            $sort_type = $sort[1];
            $models = $models->orderBy($sort_field, $sort_type);
        }

        $result = $models->paginate($limit);
        $result = $result->toArray();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function post()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        $model = (new User());
        $model->name = $params['name'];
        $model->save();

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function put()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        // $model = (new User())->where('id', '=', $params['id'])
        //     ->first();
        $model = User::query()
            ->find($params['id']);
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR_DEPT_NOT_EXISTS);
        }

        $model->name = $params['name'];
        $model->save();

        // var_dump($model->toArray());
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function delete()
    {
        $idsArray = $this->request->all();
        if (! $idsArray || ! is_array($idsArray)) {
            throw new ServiceException(ServiceCode::ERROR, [], 400, [], '数据有误');
        }

        $model = User::where('id', '=', $idsArray[0])
            ->first();
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR);
        }

        # 判断所有依赖关系 TODO

        # 删除相关表
        // UserDetail::where('dict_id', $model->id)
        // ->delete();
        // $model->delete();

        return $this->responseJson(ServiceCode::SUCCESS);
    }
}
