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

namespace App\Service\Admin;

use App\Constant\ServiceCode;
use App\Exception\ServiceException;
use App\Model\Admin;
use App\Utils\Tools;
use Hyperf\Context\Context;
use HyperfExt\Jwt\Contracts\JwtSubjectInterface;
use HyperfExt\Jwt\Jwt;

class AdminService
{
    public function list($params)
    {
        $adminModels = new Admin();
        if (isset($params['created_at']['start_time'])) {
            $adminModels->where('created_at', '>=', $params['created_at']['start_time']);
        }
        if (isset($params['created_at']['end_time'])) {
            $adminModels->where('created_at', '<=', $params['created_at']['end_time']);
        }

        return $adminModels->paginate(10);
    }

    public function usernameLogin($username, $password)
    {
        $password = Tools::encrypt($password);
        if (! $password) {
            throw new ServiceException(ServiceCode::ERROR);
        }
        $adminModel = Admin::where('username', $username)
            ->where('password', $password)
            ->select([
                'id',
                'password',
            ])
            ->first();
        if (! $adminModel) {
            throw new ServiceException(ServiceCode::ERROR_USER_USERNAME_OR_PASSWORD_ERROR);
        }
        $jwt = di(Jwt::class);
        // $jwt->setCustomClaims(['role' => 'admin']);
        /**
         * $@var JwtSubjectInterface $adminModel.
         */
        $token = $jwt->fromUser($adminModel);

        return [
            'expires_in' => config('jwt.ttl'),
            'token' => 'Bearer ' . $token,
        ];
    }

    public function putPassword($admin_model, $old_password, $new_password)
    {
        if (! $old_password = Tools::encrypt($old_password)) {
            throw new ServiceException(ServiceCode::ERROR);
        }
        if ($admin_model->password != $old_password) {
            throw new ServiceException(ServiceCode::ERROR);
        }
        if (! $new_password = Tools::encrypt($new_password)) {
            throw new ServiceException(ServiceCode::ERROR);
        }
        $admin_model->password = $new_password;
        $admin_model->save();
    }

    public static function getRefreshTokenExpiretime()
    {
        // date('Y-m-d H:i:s', strtotime('+1 day'));
        return date('Y-m-d H:i:s', time() + 3600 * 10);
    }

    public static function setSysUserId($user_id)
    {
        Context::set('sys_user_id', $user_id);
    }

    public static function getSysUserId()
    {
        return Context::get('sys_user_id');
    }
}
