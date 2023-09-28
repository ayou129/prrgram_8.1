<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Middleware\Sys;

use App\Model\SysRequestLog;
use Hyperf\Context\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestLogMiddleware implements MiddlewareInterface
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
        $requestLogModel = new SysRequestLog();
        $requestLogModel->method = $request->getMethod();
        $requestLogModel->path = $uri->getPath();
        $requestLogModel->headers = $headers;
        $requestLogModel->bodys = $request->getBody()
            ->getContents();
        $requestLogModel->ip = $uri->getHost();
        $requestLogModel->params = $request->getQueryParams();
        $requestLogModel->user_agent = $userAgent;
        $requestLogModel->save();
        // var_dump(',$requestLogModel->id',$requestLogModel->id);
        Context::set('requestLogModel', $requestLogModel);
        // var_dump(1, $userAgent);
        return $handler->handle($request);
    }
}
