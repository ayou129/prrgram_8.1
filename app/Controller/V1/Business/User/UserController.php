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

namespace App\Controller\V1\Business\User;

use App\Amqp\Producer\CommonProducer;
use App\Constant\ServiceCode;
use App\Service\Business\User\UserService;
use EasyWeChat\Factory;
use Hyperf\Amqp\Producer;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Snowflake\IdGeneratorInterface;
use Hyperf\Validation\ValidationException;

class UserController
{
    /**
     * @Inject
     * @var UserService
     */
    protected $userService;

    public function testSnowflake()
    {
        return bcadd((string) 1, '2');
        $user_model = (new Users())->save([
            'mobile' => rand(10000000000, 90000000000),
        ]);
        // $all = $this->request->all();
        // $count = $all['number'] ?? 10000;
        // $userData = [];
        // for ($i = 0; $i < $count; $i++) {
        //     $userData[] = [
        //         'mobile' => rand(10000000000, 90000000000),
        //     ];
        // }
        // User::insert($userData);
        $generator = di(IdGeneratorInterface::class);
        return $generator->generate();
    }

    public function createUser()
    {
        // throw new ServiceException(ServiceCode::ERROR_USER_NOT_EXISTS);
        // $userData = [];
        // for ($i = 0; $i < 1000; $i++) {
        //     $userData[] = [
        //         'mobile' => $i,
        //     ];
        // }
        // User::insert($userData);
        $all = $this->request->all();
        $usersModel = Users::get();
        $producer = di(Producer::class);
        foreach ($usersModel as $item) {
            $message = new CommonProducer([
                'user_id' => $item->id,
                'nickname' => $all['nickname'],
                $item->id,
            ]);
            $producer->produce($message);
        }
        return 65;
        $validator = $this->validation->make(
            $this->request->all(),
            [
                'mobile' => 'required|integer',
            ]
        );
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $this->userService->create($this->request->all());
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function wechatLogin($request)
    {
        $app = Factory::miniProgram(config('wechat.mini_program.default'));

        $result = $app->auth->session($request->vcode);

        if (empty($result['openid']) || empty($result['session_key'])) {
            // throw new ServiceException([
            //     'msg' => '失败',
            // ]);
        }

        try {
            $decrypted_data = $app->encryptor->decryptData($result['session_key'], $request->iv, $request->encrypted_data);
        } catch (\EasyWeChat\Kernel\Exceptions\DecryptException $e) {
            // throw new ServiceException([
            //     'msg' => '数据解密失败',
            // ]);
        }

        // if (isset($result['unionid'])) {
        //     # 将登陆的信息存储一下
        //     BalletmeUserAuths::saveRecord(BalletmeUserAuths::PLATFORM_TYPE_WECHAT_MINI_PROGRAM_USER, $app->getConfig()['app_id'], $result['openid'], $result['unionid']);
        // }

        $request->phone = $decrypted_data['purePhoneNumber'];
        $request->openid = $result['openid'];
        $request->unionid = $result['unionid'];
        $request->user_info = $this->userService->getDecryptData($request, $app, $result['session_key']);

        return $this->userService->login($request);
    }

    public function mobileLogin()
    {
        $validator = $this->validation->make(
            $this->request->all(),
            [
                'mobile' => 'required|integer',
                'code' => 'required|integer',
            ]
        );
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $tokenInfo = $this->userService->mobileLogin($this->request->input('mobile'));
        return $this->responseJson(ServiceCode::SUCCESS, $tokenInfo);
    }
}
