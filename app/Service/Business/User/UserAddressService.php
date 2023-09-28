<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service\Business\User;

use App\Constant\ServiceCode;
use App\Exception\ServiceException;
use App\Model\UserAddress as UserAddressModel;
use Hyperf\DbConnection\Db;

class UserAddressService
{
    public function getById($id, $user_id)
    {
        return UserAddressModel::where('id', $id)
            ->where('user_id', $user_id)
            ->first();
    }

    public function getList($user_id)
    {
        return UserAddressModel::where('user_id', $user_id)
            ->paginate(10);
    }

    public function post($params, $user_id)
    {
        Db::beginTransaction();
        try {
            $oldModel = (new UserAddressModel())->where('name', $params['name'])
                ->where('mobile_number', $params['mobile_number'])
                ->where('province', $params['province'])
                ->where('city', $params['city'])
                ->where('area', $params['area'])
                ->where('detail', $params['detail'])
                ->where('user_id', $user_id)
                ->first();
            if ($oldModel) {
                throw new ServiceException(ServiceCode::ERROR_USER_ADDRESS_REPEAT);
            }

            $model = new UserAddressModel();
            $model->name = $params['name'];
            $model->mobile_number = $params['mobile_number'];
            $model->province = $params['province'];
            $model->city = $params['city'];
            $model->area = $params['area'];
            $model->detail = $params['detail'];
            $model->is_default = $params['is_default'];
            $model->user_id = $user_id;
            if ($params['is_default'] === true) {
                (new UserAddressModel())->where('user_id', $user_id)
                    ->save(['is_default' => false]);
            }
            $model->save();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function put($params, $user_id)
    {
        Db::beginTransaction();
        try {
            $model = UserAddressModel::where('id', $params['address_id'])
                ->where('user_id', $user_id)
                ->first();
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR_USER_ADDRESS_NOT_EXISTS);
            }

            $model->name = $params['name'];
            $model->mobile_number = $params['mobile_number'];
            $model->province = $params['province'];
            $model->city = $params['city'];
            $model->area = $params['area'];
            $model->detail = $params['detail'];
            $model->is_default = $params['is_default'];
            if ($params['is_default'] === true) {
                (new UserAddressModel())->where('user_id', $user_id)
                    ->save(['is_default' => false]);
            }
            $model->save();
            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }

    public function delete($params, $user_id)
    {
        Db::beginTransaction();
        try {
            $model = UserAddressModel::where('id', $params['address_id'])
                ->where('user_id', $user_id)
                ->first();
            if (! $model) {
                throw new ServiceException(ServiceCode::ERROR_USER_ADDRESS_NOT_EXISTS);
            }

            $model->delete();

            Db::commit();
            return true;
        } catch (\Throwable $e) {
            Db::rollBack();
            throw $e;
        }
    }
}
