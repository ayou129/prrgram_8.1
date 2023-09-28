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

namespace App\Middleware;

use App\Constant\ServiceCode;
use App\Exception\ServiceException;
use App\Model\SysUser;
use App\Service\Admin\AdminService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Exception\HttpException;
use HyperfExt\Jwt\Exceptions\JwtException;
use HyperfExt\Jwt\Exceptions\TokenExpiredException;
use HyperfExt\Jwt\Jwt;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class AuthMiddleware implements MiddlewareInterface
{
    /**
     * @Inject
     * @var ContainerInterface
     */
    protected $container;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // var_dump($request->getHeaders());
        $tokenArray = $request->getHeader('Authorization');
        if (! $tokenArray || ! $token = $tokenArray[0]) {
            throw new HttpException(401, '未授权');
        }
        // var_dump($token);
        // 检查Token，是否过期
        $sysUserModel = SysUser::where('token', $token)->first();

        if (! $sysUserModel) {
            throw new HttpException(401, '验证失败');
        }

        if (! $sysUserModel->token_expiretime || ! $token_expiretime = strtotime($sysUserModel->token_expiretime)) {
            throw new HttpException(401, '令牌已失效，请重新登录', -1);
        }
        if (($token_expiretime - time()) < 0) {
            throw new HttpException(401, '令牌已失效，请重新登录', -1);
        }

        // 验证通过记录当前user_id,增长token过期时间
        AdminService::setSysUserId($sysUserModel->id);
        $sysUserModel->token_expiretime = AdminService::getRefreshTokenExpiretime();
        $sysUserModel->save();

        return $handler->handle($request);
    }

    // /**
    //  * @Inject
    //  * @var Jwt
    //  */
    // protected $jwt;

    // public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    // {
    //     // 使用 try 包裹，以捕捉 token 过期所抛出的 TokenExpiredException  异常
    //     try {
    //         // 检测用户的登录状态，如果正常则通过
    //         $token = $this->jwt->check();
    //         // var_dump($this->jwt->getClaim('sub'));
    //         if ($token === null) {
    //             throw new ServiceException(ServiceCode::ERROR_USER_AUTH_FAIL, [], 401);
    //         }
    //     } catch (TokenExpiredException $exception) {
    //         // 此处捕获到了 token 过期所抛出的 TokenExpiredException 异常，我们在这里需要做的是刷新该用户的 token 并将它添加到响应头中
    //         try {
    //             // 刷新用户的 token
    //             $token = $this->jwt->refresh();
    //             // 在响应头中返回新的 token
    //             $request->withHeader('token', $token);
    //             // 使用一次性登录以保证此次请求的成功
    //             // Auth::guard('api')->onceUsingId($this->auth->manager()->getPayloadFactory()->buildClaimsCollection()->toPlainArray()['sub']);
    //         } catch (JwtException $exception) {
    //             // 如果捕获到此异常，即代表 refresh 也过期了，用户无法刷新令牌，需要重新登录。
    //             throw new ServiceException(ServiceCode::ERROR_USER_AUTH_FAIL, [], 401);
    //         }
    //     }
    //     return $handler->handle($request);
    // }
}
