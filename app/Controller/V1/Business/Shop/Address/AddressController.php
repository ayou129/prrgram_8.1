<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Controller\V1\Business\Shop\Address;

// use app\common\service\Lang;
// use app\common\validate\AddressValidate;
use App\Constant\ServiceCode;
use App\Controller\BaseController;
use App\Exception\ServiceException;
use App\Service\Business\User\UserAddressService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\Validation\ValidationException;

#[AutoController]
class AddressController extends BaseController
{
    /**
     * @Inject
     */
    protected UserAddressService $userAddressService;

    public function getAddressById()
    {
        $userModel = getLoginModel();
        $validator = $this->validation->make(
            $this->request->all(),
            [
                'address_id' => 'required|integer|max:100000000',
            ]
        );
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $model = $this->userAddressService->getById($this->request->input('address_id'), $userModel->id);
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR_USER_ADDRESS_NOT_EXISTS);
        }
        return $this->responseJson(ServiceCode::SUCCESS, $model);
    }

    public function getAddressList()
    {
        $userModel = getLoginModel();
        return $this->responseJson(ServiceCode::SUCCESS, $userModel->toArray());
        $userModel = getLoginModel();
        $models = $this->userAddressService->getList($userModel->id);
        return $this->responseJson(ServiceCode::SUCCESS, $models);
    }

    public function postAddress()
    {
        $userModel = getLoginModel();
        $validator = $this->validation->make(
            $this->request->all(),
            [
                'name|收货人' => 'require|length:1,45',
                'mobile_number|收货人电话号码' => 'require',
                'province|省' => 'require|length:1,45',
                'city|城市' => 'require|length:1,45',
                'area|区' => 'require|length:1,45',
                'detail|详细地址' => 'require|length:1,100',
                'is_default|默认地址状态' => 'require|bool',
            ]
        );
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $this->userAddressService->post($this->request, $userModel->id);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function putAddress()
    {
        $validator = $this->validation->make(
            $this->request->all(),
            [
                'address_id|地址ID' => 'require|integer|between:1,100000000',
                'name|收货人' => 'require|length:1,45',
                'mobile_number|收货人电话号码' => 'require',
                'province|省' => 'require|length:1,45',
                'city|城市' => 'require|length:1,45',
                'area|区' => 'require|length:1,45',
                'detail|详细地址' => 'require|length:1,100',
                'is_default|默认地址状态' => 'require|bool',
            ]
        );
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $this->userAddressService->put($this->request, 100);
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function deleteAddress()
    {
        $validator = $this->validation->make(
            $this->request->all(),
            [
                'address_id' => 'required|integer|max:100000000',
            ]
        );
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        $this->userAddressService->delete($this->request->input('address_id'), 100);
        return $this->responseJson(ServiceCode::SUCCESS);
    }
}
