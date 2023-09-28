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

namespace App\Service\Utils\Wechat;

use EasyWeChat\Factory;
use Exception;
use Hyperf\Guzzle\CoroutineHandler;
use LogicException;

/**
 * 微信服务.
 */
abstract class WechatService
{
    protected object $app;

    public function __construct(string $type)
    {
        try {
            switch ($type) {
                case 'miniProgram':
                    $this->app = Factory::miniProgram($this->getConfig());
                    break;
                case 'officialAccount':
                    $this->app = Factory::officialAccount($this->getConfig());
                    break;
                case 'openPlatform':
                    $this->app = Factory::openPlatform($this->getConfig());
                    break;
                case 'openWork':
                    $this->app = Factory::openWork($this->getConfig());
                    break;
                case 'payment':
                    $this->app = Factory::payment($this->getConfig());
                    break;
                case 'work':
                    $this->app = Factory::work($this->getConfig());
                    break;
                default:
                    throw new LogicException();
            }
            $app['guzzle_handler'] = CoroutineHandler::class;
            var_dump($app);
        } catch (Exception $e) {
            throw new LogicException($e->getMessage());
        }
    }

    protected function getConfig(): array
    {
        return config('wechat') ? config('wechat') : [];
    }
}
