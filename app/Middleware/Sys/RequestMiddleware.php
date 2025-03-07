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

namespace App\Middleware\Sys;

use App\Model\SysRequest;
use Hyperf\Context\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $uri = $request->getUri();
        $headers = $request->getHeaders();
        $userAgent = '';
        foreach ($headers as $key => $value) {
            if ($key === 'user-agent') {
                $userAgent = $value;
                break;
            }
        }

        if ($request->getMethod() !== 'OPTIONS') {
            $requestLogModel = new SysRequest();
            $requestLogModel->method = $request->getMethod();
            $requestLogModel->path = $uri->getPath();
            $requestLogModel->headers = $headers;
            $requestLogModel->bodys = $request->getBody()
                ->getContents();
            $requestLogModel->ip = $uri->getHost();
            $requestLogModel->params = $request->getQueryParams();
            $requestLogModel->user_agent = $userAgent;
            $requestLogModel->save();
            Context::set('requestModel', $requestLogModel);
        }
        return $handler->handle($request);
    }
}
