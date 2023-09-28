<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Service\Business\User;

use App\Constant\ServiceCode;
use App\Event\UserRegister;
use App\Exception\ServiceException;
use App\Model\User;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use HyperfExt\Jwt\Jwt;
use Psr\EventDispatcher\EventDispatcherInterface;

class UserService
{
    /**
     * @Inject
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function getList($params = [])
    {
        if (! isset($params['per_page'])) {
            $params['per_page'] = 10;
        }

        return User::paginate($params['per_page']);
    }

    public function create($params)
    {
        Db::beginTransaction();
        try {
            $userModel = User::where('mobile', $params['mobile'])
                ->first();
            if ($userModel) {
                throw new ServiceException(ServiceCode::ERROR_USER_USERNAME_OR_PASSWORD_ERROR);
            }
            $userModel = new User();
            $userModel->mobile = $params['mobile'];
            $userModel->save();
            $this->eventDispatcher->dispatch(new UserRegister($userModel));
            Db::commit();
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    public function wechatLogin($params) {}

    public function mobileLogin($mobile)
    {
        $userModel = User::where('mobile', $mobile)
            ->select([
                'id',
                'mobile',
            ])
            ->first();
        if (! $userModel) {
            throw new ServiceException(ServiceCode::ERROR_USER_NOT_EXISTS);
        }
        $jwt = di(Jwt::class);
        $token = $jwt->fromUser($userModel);
        return [
            'expires_in' => config('jwt.ttl'),
            'access_token' => $token,
        ];
    }

    /**
     * 获取用户解密信息.
     * @param mixed $request
     * @param mixed $app
     * @param mixed $session_key
     */
    public function getDecryptData($request, $app, $session_key)
    {
        if ($request->iv_user && $request->encrypted_data_user) {
            $user_decrypted_data = $app->encryptor->decryptData($session_key, $request->iv_user, $request->encrypted_data_user);
        } else {
            $user_decrypted_data = [
                'gender' => 0,
                'avatarUrl' => '',
                'city' => '',
                'province' => '',
                'country' => '',
            ];
        }

        return $user_decrypted_data;
    }

    public function login($request)
    {
        $this->request = $request;

        $user = BalletmeUser::phone($request->phone)
            ->select('id', 'gender', 'status')
            ->first();

        $openid = $request->openid;

        $user_info = $request->user_info;
        if (! $user) {
            $status = BalletmeUser::STATUS_TRUE;

            if ($user_info['gender'] == BalletmeUser::GENDER_MALE) {
                // TODO
                /* $status = BalletmeUser::STATUS_FALSE; */
            }

            if (! empty($request->latitude) && ! empty($request->longitude)) {
                $city = (new Tool())->locationToCity($request->longitude, $request->latitude);
            }

            $user_data = [
                'openid' => $openid,
                'name' => '',
                'avatar' => $user_info['avatarUrl'],
                'phone' => $request->phone,
                'city' => $city ?? $user_info['city'],
                'difficulty' => 0,
                'province' => $user_info['province'],
                'country' => $user_info['country'],
                'gender' => (int) $user_info['gender'],
                'unionid' => $request->unionid,
                'status' => $status,
            ];

            $user = $this->create($user_data);
        } else {
            $user->openid = $openid;
            $user->unionid = $request->unionid;
            $user->avatar = $user_info['avatarUrl'];
            $user->city = $user_info['city'];
            $user->province = $user_info['province'];
            $user->country = $user_info['country'];
            $user->gender = $user->gender ?: (int) $user_info['gender'];
            $user->save();
        }

        if ($user->status == BalletmeUser::STATUS_FALSE) {
            throw new ServiceException('小仙女的账号异常，请微信联系客服balletme3看看哦。');
        }

        $token = auth('api')->fromUser($user);

        return [
            'token' => $this->respondWithToken($token),
            'user' => $user,
        ];
    }
}
