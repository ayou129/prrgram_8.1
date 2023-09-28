<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Controller;

use App\Constant\ServiceCode;
use App\Utils\Tools;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;

#[AutoController]
class BaseController extends AbstractController
{
    public function index(RequestInterface $request, ResponseInterface $response)
    {
        if (Tools::isProduct()) {
            return $this->responseJson(ServiceCode::WELCOME);
        }
        // 执行 phpinfo() 函数
        ob_start();
        phpinfo();
        $phpInfo = ob_get_clean();

        // 将结果输出到浏览器页面
        $response->withHeader('Content-Type', 'text/html; charset=utf-8');
        $response->getBody()->write($phpInfo);

        return $response;
    }
}
