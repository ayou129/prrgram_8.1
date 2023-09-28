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
use App\Model\User;
use App\Model\UserPlatform;
use App\Service\Business\UserService;
use App\Utils\Tools;
use EasyWeChat\Factory;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Throwable;

class MiniLoginService
{
    private array $config = [];

    private int $platform;

    /**
     * @Inject
     * @var UserService
     */
    private $userService;

    public function __construct()
    {
        $this->platform = UserPlatform::PLATFORM_MINIPROGRAM;

        $this->config = [
            'app_id' => env('WX_MINIPROGRAM_APPID', ''),
            'secret' => env('WX_MINIPROGRAM_SECRET', ''),
            // 下面为可选项
            // 指定 API 调用返回结果的类型：array(default)/collection/object/raw/自定义类名
            'response_type' => 'array',
            'log' => [
                'level' => 'debug',
                'file' => __DIR__ . '/wechat.log',
            ],
        ];
    }

    public function registerAndLoginByPhone($params)
    {
        /*
         * 注册(包括Login)则更新token
         */
        try {
            $app = Factory::miniProgram($this->config);
            $wxPhoneResult = $app->phone_number->getUserPhoneNumber($params['code']);
            $wxResult = $app->auth->session($params['wx_login_code']);
            //            var_dump($wxPhoneResult, $wxResult);
            if (! isset($wxPhoneResult['phone_info']['purePhoneNumber'])) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, $wxPhoneResult);
            }
        } catch (InvalidConfigException $ie) {
            var_dump($ie->getMessage());
            throw new Exception($ie->getMessage());
        } catch (GuzzleException $ge) {
            var_dump($ge->getMessage());
            throw new Exception($ge->getMessage());
        }
        $phone = $wxPhoneResult['phone_info']['purePhoneNumber'];
        //        $phone = '15622535674';
        //        $wxResult['openid'] = 'oCNty69xTU_wez04hVPvn56BpmpI';
        Db::beginTransaction();
        try {
            $userModel = User::getCacheUserByPhone($phone);
            if (! $userModel) {
                $password = Tools::encrypt(substr($phone, -6, 6));
                $userModel = new User();
                $userModel->phone = $phone;
                $userModel->password = $password;
                $userModel->save();
            }

            /**
             * 有可能user_platform不存在
             * 1. 确实不存在，第一次创建
             * 2. 手动删除了数据，脏数据，新创建.
             */
            $userPlatformModel = UserPlatform::getCacheByWxPlatformAndUserIdAndOpenid($this->platform, $userModel->id, $wxResult['openid'], ['user']);

            if (! $userPlatformModel) {
                $userPlatformModel = new UserPlatform();
                $userPlatformModel->platform = $this->platform;
                $userPlatformModel->u_id = $userModel->id;
                $userPlatformModel->wx_openid = $wxResult['openid'];
                $userPlatformModel->wx_session_key = $wxResult['session_key'];
                $userPlatformModel->save();
                $userPlatformModel->user = $userModel;

                $tokenInfo = $this->userService->reletToken($userPlatformModel, true);
            } else {
                $userPlatformModel->wx_session_key = $wxResult['session_key'];
                $userPlatformModel->save();

                if (! $userModel->user) {
                    $userPlatformModel->user = $userModel;
                }
                $tokenInfo = $this->userService->reletToken($userPlatformModel);
            }
            // else {
            //     // 强制更新平台表的u_id
            //     $userPlatformModel->u_id = $userModel->id;
            // }

            Db::commit();
            return $tokenInfo;
        } catch (Throwable $ex) {
            Db::rollBack();
            throw $ex;
        }
    }

    //    public function miniLogin($params)
    //    {
    //        try {
    //            $app = Factory::miniProgram($this->config);
    //            $wxResult = $app->auth->session($params['code']);
    //            // var_dump($wxResult);
    //            if (! isset($wxResult['openid'], $wxResult['session_key'])) {
    //                throw new \Exception($wxResult);
    //            }
    //        } catch (InvalidConfigException $e) {
    //            throw new \Exception($e->getMessage());
    //        }
    //        Db::beginTransaction();
    //        try {
    //            $userPlatformModel = $this->getUserPlatformModelByOpenid($wxResult['openid']);
    //            if (! $userPlatformModel) {
    //                throw new ServiceException(ServiceCode::ERROR, [], 401, $wxResult);
    //            }
    //            // 增加登录记录
    //            $this->userService->recordLoginInfo($userPlatformModel->u_id, $this->platform, $params['ip']);
    //
    //            // 处理session
    //            $userPlatformModel->wx_session_key = $wxResult['session_key'];
    //            $userPlatformModel->save();
    //            $tokenInfo = $this->userService->reletToken($userPlatformModel);
    //            // 续租Token
    //            Db::commit();
    //            return $tokenInfo;
    //        } catch (\Throwable $ex) {
    //            Db::rollBack();
    //            throw $ex;
    //        }
    //    }

    public function checkUserPlatformExists($params): array
    {
        $userPlatformModel = $this->getUserPlatformModelByToken($params);
        return ['status' => $userPlatformModel ? true : false];
    }

    public function checkAndReletToken($params)
    {
        $userPlatformModel = $this->getUserPlatformModelByToken($params);
        if (! $userPlatformModel) {
            throw new ServiceException(ServiceCode::ERROR, [], 401);
        }

        // 检查login_token是否过期,过期则返回401重新登录
        $userLoginToken = $this->userService->checkUserLoginToken($userPlatformModel);
        if (! $userLoginToken) {
            throw new ServiceException(ServiceCode::ERROR, [], 401);
        }

        // 如果令牌需要更新，只更新过期时间
        $this->userService->reletToken($userPlatformModel, false, true);

        return $userPlatformModel;
    }

    //    public function getUserPlatformModelByOpenid($params)
    //    {
    //        if (! isset($params['openid']) || ! $params['openid']) {
    //            return false;
    //        }
    //        $userPlatformModel = UserPlatform::getCacheByWxPlatformAndUserIdAndOpenid($this->platform, $params['openid'], ['user']);
    //
    //        $status = $this->checkUserPlatformModel($userPlatformModel);
    //        if (! $status) {
    //            return false;
    //        }
    //
    //        return $userPlatformModel;
    //    }

    public function getUserPlatformModelByToken($params)
    {
        if (! isset($params['token']) || ! $params['token']) {
            return false;
        }
        # # save
        $userPlatformModel = UserPlatform::getCacheByTokenAndPlatform($this->platform, $params['token'], ['user']);
        if (! $userPlatformModel) {
            return false;
        }

        $status = $this->checkUserPlatformModel($userPlatformModel);
        if (! $status) {
            return false;
        }
        return $userPlatformModel;
    }

    private function checkUserPlatformModel($userPlatformModel): bool
    {
        if (! $userPlatformModel || ! $userPlatformModel->user) {
            return false;
        }
        return true;
    }
}
