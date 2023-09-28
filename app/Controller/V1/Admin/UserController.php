<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Controller\V1\Admin;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use App\Exception\ServiceException;
use App\Model\SysDept;
use App\Model\SysJob;
use App\Model\SysRole;
use App\Model\SysUser;
use App\Model\SysUsersJob;
use App\Model\SysUsersRole;
use Hyperf\HttpServer\Annotation\AutoController;

/**
 * Class UserController.
 * @AutoController
 */
class UserController extends AbstractController
{
    public function list()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $limit = (int) $this->request->input('size', 10);
        $models = (new SysUser());
        $params['sort'] = $this->request->input('sort') ?? [];
        foreach ($params['sort'] as $item) {
            $sort = explode(',', $item);
            $sort_field = $sort[0];
            $sort_type = $sort[1];
            $models = $models->orderBy($sort_field, $sort_type);
        }
        $where = $whereOr = [];

        if (isset($params['dept_id'])) {
            $where[] = [
                'dept_id',
                '=',
                $params['dept_id'],
            ];
        }
        if (isset($params['created_at_start_time'])) {
            $where[] = [
                'created_at',
                '>=',
                $params['created_at_start_time'],
            ];
        }
        if (isset($params['created_at_end_time'])) {
            $where[] = [
                'created_at',
                '<=',
                $params['created_at_end_time'],
            ];
        }
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
        $models = $models->where($where)
            ->where(function ($query) use ($whereOr) {
                foreach ($whereOr as $item) {
                    $query->where(...$item[0])
                        ->orWhere(...$item[1]);
                }
            })
            ->with([
                'dept',
                'jobs',
                'roles',
            ]);
        $result = $models->paginate($limit);

        $result = $result->toArray();
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function create()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        # 不能重复 title、component_name(name)
        $exists = SysUser::where('username', '=', $params['username'])
            ->count();
        if ($exists) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }

        if (! isset($params['dept_id'], $params['jobs'], $params['roles'])) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }

        $existsDept = SysDept::where('id', '=', $params['dept_id'])
            ->count();
        if (! $existsDept) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }

        $jobsIds = $rolesIds = [];
        foreach ($params['jobs'] as $item) {
            $jobsIds[] = $item['id'];
        }
        foreach ($params['roles'] as $item) {
            $rolesIds[] = $item['id'];
        }
        $jobsIds = array_unique($jobsIds);
        $rolesIds = array_unique($rolesIds);
        $existsJobs = SysJob::whereIn('id', $jobsIds)
            ->count();
        if ($existsJobs !== count($jobsIds)) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }
        $existsRoles = SysRole::whereIn('id', $rolesIds)
            ->count();
        if ($existsRoles !== count($rolesIds)) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }

        $model = (new SysUser());
        $model->dept_id = $params['dept_id'];
        $model->username = $params['username'];
        $model->password = '123456';
        $model->nick_name = $params['nick_name'];
        $model->gender = $params['gender'];
        $model->phone = $params['phone'];
        $model->email = $params['email'];
        // $model->is_admin = ;
        $model->enabled = $params['enabled'];
        $model->create_by = $params['create_by'] ?? 'admin';
        $model->update_by = $params['update_by'] ?? 'admin';
        $model->save();

        # 插入users_roles
        $prepareSaveDataUsersJobs = [];
        $prepareSaveDataUsersRoles = [];
        foreach ($jobsIds as $item) {
            $prepareSaveDataUsersJobs[] = [
                'user_id' => $model->id,
                'job_id' => $item,
            ];
        }
        foreach ($rolesIds as $item) {
            $prepareSaveDataUsersRoles[] = [
                'user_id' => $model->id,
                'role_id' => $item,
            ];
        }

        # 插入users_jobs
        SysUsersJob::insert($prepareSaveDataUsersJobs);
        # 插入users_roles
        SysUsersRole::insert($prepareSaveDataUsersRoles);

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function put()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        # 不能重复 title、component_name(name)
        $model = SysUser::where('id', '=', $params['id'])
            ->first();
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }

        $exists = SysUser::where('username', '=', $params['username'])
            ->where('id', '<>', $params['id'])
            ->count();
        if ($exists) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }

        if (! isset($params['dept_id'], $params['jobs'], $params['roles'])) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }

        $existsDept = SysDept::where('id', '=', $params['dept_id'])
            ->count();
        if (! $existsDept) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }

        $jobsIds = $rolesIds = [];
        foreach ($params['jobs'] as $item) {
            $jobsIds[] = $item['id'];
        }
        foreach ($params['roles'] as $item) {
            $rolesIds[] = $item['id'];
        }
        $jobsIds = array_unique($jobsIds);
        $rolesIds = array_unique($rolesIds);
        $existsJobs = SysJob::whereIn('id', $jobsIds)
            ->count();
        if ($existsJobs !== count($jobsIds)) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }
        $existsRoles = SysRole::whereIn('id', $rolesIds)
            ->count();
        if ($existsRoles !== count($rolesIds)) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }

        $model->dept_id = $params['dept_id'];
        $model->username = $params['username'];
        // $model->password = '123456';
        $model->nick_name = $params['nick_name'];
        $model->gender = $params['gender'];
        $model->phone = $params['phone'];
        $model->email = $params['email'];
        // $model->is_admin = ;
        $model->enabled = $params['enabled'];
        // $model->create_by = $params['create_by'] ?? 'admin';
        $model->update_by = $params['update_by'] ?? 'admin';
        $model->save();

        # 插入users_roles
        $prepareSaveDataUsersJobs = [];
        $prepareSaveDataUsersRoles = [];
        foreach ($jobsIds as $item) {
            $prepareSaveDataUsersJobs[] = [
                'user_id' => $model->id,
                'job_id' => $item,
            ];
        }
        foreach ($rolesIds as $item) {
            $prepareSaveDataUsersRoles[] = [
                'user_id' => $model->id,
                'role_id' => $item,
            ];
        }

        # 插入users_jobs
        SysUsersJob::where('user_id', $model->id)
            ->delete();
        SysUsersJob::insert($prepareSaveDataUsersJobs);
        # 插入users_roles
        SysUsersRole::where('user_id', $model->id)
            ->delete();
        SysUsersRole::insert($prepareSaveDataUsersRoles);

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function delete()
    {
        $params = $this->getRequestAllFilter();
        $params['id'] = $params[0];
        // var_dump($params);

        $model = SysUser::where('id', '=', $params['id'])
            ->first();
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }
        # 判断所有依赖关系 TODO

        # 删除相关表
        SysUsersJob::where('user_id', $model->id)
            ->delete();
        SysUsersRole::where('user_id', $model->id)
            ->delete();
        $model->delete();

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function updatePass()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $token = $this->request->header('Authorization');
        if (! isset($token, $params['oldPass'])) {
            return $this->responseJson(ServiceCode::ERROR_PARAM_MISSING);
        }

        # 不能重复 title、component_name(name)
        $model = SysUser::where('token', $token)
            ->where('password', $params['oldPass'])
            ->first();
        if (! $model) {
            # 密码错误
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
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
}
