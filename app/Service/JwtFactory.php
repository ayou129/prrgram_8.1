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

namespace App\Service;

use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpMessage\Server\Request;
use HyperfExt\Jwt\Jwt;
use HyperfExt\Jwt\Manager;
use HyperfExt\Jwt\RequestParser\Handlers\AuthHeaders;
use HyperfExt\Jwt\RequestParser\RequestParser;
use Psr\Container\ContainerInterface;

class JwtFactory
{
    public function __invoke(ContainerInterface $container): Jwt
    {
        $config = $container->get(ConfigInterface::class);
        // 我们假设对应的配置的 key 为 cache.enable
        $lockSubject = (bool) $config->get('jwt.lock_subject');
        $parser = $container->get(RequestParser::class);
        $parser->setHandlers([
            new AuthHeaders(),
        ]);
        return make(Jwt::class, [
            'parser' => $parser,
            'manager' => $container->get(Manager::class),
            'request' => $container->get(Request::class),
        ])->setLockSubject($lockSubject);
    }
}
