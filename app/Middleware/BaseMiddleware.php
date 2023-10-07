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

use App\Utils\Tools;
use Hyperf\Context\Context;
use Hyperf\Contract\TranslatorInterface;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class BaseMiddleware implements MiddlewareInterface
{
    protected ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $params = $request->getQueryParams();
        if (! Tools::isProduct()) {
            // var_dump(date('Y-m-d H:i:s'), $params);
        }
        # 语言匹配
        $container = ApplicationContext::getContainer();
        $translator = $container->get(TranslatorInterface::class);
        $currentLang = $params['lang'] ?? 'zh_CN';
        $allowLang = config('allow_lang', []);
        if (in_array(
            $currentLang,
            $allowLang,
            true
        )) {
            $translator->setLocale($currentLang);
        }

        # 为每一个请求增加一个qid
        $request = Context::override(ServerRequestInterface::class, function (ServerRequestInterface $request) {
            // $id = $this->getRequestId();
            // var_dump($id);
            return $request;
        });

        # 利用协程上下文存储请求开始的时间，用来计算程序执行时间
        Context::set('request_start_time', microtime(true));

        $response = $handler->handle($request);

        # 洋葱模型出来之后
        $executionMicroTime = bcsub((string) microtime(true), (string) Context::get('request_start_time'), 20);
        $executionSecond = bcdiv($executionMicroTime, '1000000', 20);
        $response = $response->withAddedHeader('Execution-Second', $executionSecond);
        $response = $response->withAddedHeader('Server-Language', $translator->getLocale());
        $response = $response->withoutHeader('Server');
        $response = $response->withAddedHeader('Server', config('app_name'));
        return $response->withAddedHeader('Request-Type', 'http');
    }

    // public function getServerLocalIp(): string
    // {
    //     $ip = '127.0.0.1';
    //     $ips = array_values(swoole_get_local_ip());
    //     foreach ($ips as $v) {
    //         if ($v && $v != $ip) {
    //             $ip = $v;
    //             break;
    //         }
    //     }
    //
    //     return $ip;
    // }

    // private function getRequestId()
    // {
    //     // $tmp = $this->request->getServerParams();
    //     $tmp = ['remote_addr' => '127.0.0.1'];
    //     // var_dump($tmp);
    //     $name = strtoupper(substr(md5(gethostname()), 12, 8));
    //     $remote = strtoupper(substr(md5($tmp['remote_addr']), 12, 8));
    //     $ip = strtoupper(substr(md5($this->getServerLocalIp()), 14, 4));
    //     return uniqid() . '-' . $remote . '-' . $ip . '-' . $name;
    // }
}
