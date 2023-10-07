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

namespace App\Controller;

use App\Constant\ServiceCode;
use App\Exception\ServiceException;
use App\Model\User;
use Hyperf\HttpServer\Contract\RequestInterface;

class TestController extends AbstractController
{
    public function testException(RequestInterface $request)
    {
        $params = $this->getRequestAllFilter();
        if (! isset($params['type'])) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
        }
        switch ($params['type']) {
            case 1 :
                // 1.系统异常
                $a = 1 / 0;
                break;
            case 2:
                // 2.业务异常
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            case 3:
                // 2.Mysql异常
                $user = new User();
                $user->sadads = 1;
                $user->save();
                break;
            default:
                break;
        }
    }
}
