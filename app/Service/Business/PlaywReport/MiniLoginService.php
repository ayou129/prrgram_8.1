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
use EasyWeChat\Kernel\Exceptions\DecryptException;
use EasyWeChat\Kernel\Exceptions\HttpException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use EasyWeChat\MiniApp\Application;
use Exception;
use Hyperf\DbConnection\Db;
use Hyperf\Di\Annotation\Inject;
use Throwable;

class MiniLoginService
{
    #[Inject]
    protected UserService $userService;

    private array $config = [];

    private int $platform;

    public function __construct()
    {
        $this->platform = UserPlatform::PLATFORM_MINIPROGRAM;

        $this->config = [
            'app_id' => env('WX_MINIPROGRAM_APPID', ''),
            'secret' => env('WX_MINIPROGRAM_SECRET', ''),
            'token' => env('WX_MINIPROGRAM_TOKEN', ''),
            'aes_key' => env('WX_MINIPROGRAM_AES_KEY', ''),
            /*
             * 接口请求相关配置，超时时间等，具体可用参数请参考：
             * https://github.com/symfony/symfony/blob/5.3/src/Symfony/Contracts/HttpClient/HttpClientInterface.php
             */
            'http' => [
                'throw' => true,
                // 状态码非 200、300 时是否抛出异常，默认为开启
                'timeout' => 5.0,
                // 'base_uri' => 'https://api.weixin.qq.com/', // 如果你在国外想要覆盖默认的 url 的时候才使用，根据不同的模块配置不同的 uri

                'retry' => true,
                // 使用默认重试配置
                //  'retry' => [
                //      // 仅以下状态码重试
                //      'http_codes' => [429, 500]
                //       // 最大重试次数
                //      'max_retries' => 3,
                //      // 请求间隔 (毫秒)
                //      'delay' => 1000,
                //      // 如果设置，每次重试的等待时间都会增加这个系数
                //      // (例如. 首次:1000ms; 第二次: 3 * 1000ms; etc.)
                //      'multiplier' => 3
                //  ],
            ],
        ];
    }

    public function registerAndLoginByPhone($params)
    {
        /*
         * 注册(包括Login)则更新token
         */
        try {
            /**
             * wx_login_code wx.login的code.
             * code、xx getPhoneNumber.
             */
            $app = new Application($this->config);
            $utils = $app->getUtils();
            $wxResult = $utils->codeToSession($params['wx_login_code']);
            // var_dump($wxResult);

            $wxPhoneResult = $utils->decryptSession($wxResult['session_key'], $params['iv'], $params['encryptedData']);
            if (! isset($wxPhoneResult['purePhoneNumber'])) {
                throw new ServiceException(ServiceCode::ERROR, [], 400, $wxPhoneResult);
            }
            var_dump($wxPhoneResult, $wxResult);
        } catch (DecryptException $ie) {
            var_dump($ie->getMessage());
            throw new ServiceException(ServiceCode::ERROR, [], 401, [], $ie->getMessage());
        } catch (HttpException $ge) {
            var_dump($ge->getMessage());
            throw new ServiceException(ServiceCode::ERROR, [], 401, [], $ge->getMessage());
        }
        $phone = $wxPhoneResult['purePhoneNumber'];
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
            $userPlatformModel = UserPlatform::getCacheByWxPlatformAndUserIdAndOpenid($this->platform, $userModel->id, $wxResult['openid']);

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

                $userPlatformModel->user = User::getCacheById($userPlatformModel->u_id);
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
        $userPlatformModel = UserPlatform::getCacheByTokenAndPlatform($this->platform, $params['token']);
        if (! $userPlatformModel) {
            return false;
        }

        $userPlatformModel->user = User::getCacheById($userPlatformModel->u_id);

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
