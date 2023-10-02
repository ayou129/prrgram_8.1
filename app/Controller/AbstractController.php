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
use App\Utils\Tools;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use LogicException;
use Psr\Container\ContainerInterface;
use ReflectionClass;

abstract class AbstractController
{
    #[Inject]
    protected ContainerInterface $container;

    #[Inject]
    protected RequestInterface $request;

    #[Inject]
    protected ResponseInterface $response;

    /**
     * 输出结果，并且将文字进行翻译.
     * 小程序是否显示弹窗，是否回退页面，是否跳转页面.
     */
    protected function responseJson(int $code, array $data = [], string $custom_msg = ''): \Psr\Http\Message\ResponseInterface
    {
        if ($custom_msg) {
            $msg = $custom_msg;
        } else {
            $reflectionClass = new ReflectionClass(ServiceCode::class);
            $statusCode = $reflectionClass->getConstants();
            $target = false;
            foreach ($statusCode as $key => $item) {
                if ($item === $code) {
                    $target = $key;
                    break;
                }
            }
            if (! $target) {
                throw new LogicException('错误的$code 或 缺少StatusCode！');
            }
            $msg = __('messages.' . $target) ?? '';
        }

        $json = [
            'msg' => $msg,
            'data' => $data,
            'code' => $code,
        ];
        return $this->response->json($json);
    }

    /**
     * 输出结果，并且将文字进行翻译.
     * 小程序是否显示弹窗，是否回退页面，是否跳转页面.
     * @param mixed $msg
     * @param mixed $data
     * @param mixed $code
     */
    protected function resJson($msg, $data = [], $code = 0): \Psr\Http\Message\ResponseInterface
    {
        $json = [
            'msg' => $msg,
            'data' => $data,
            'code' => $code,
        ];
        return $this->response->json($json);
    }

    protected function getRequestAllFilter(): array
    {
        $array = $this->request->all();
        $array['token'] = $this->request->header('Authorization');
        // $array = [
        //     1,
        //     03,
        //     ' 01 ',
        //     ' 02 ',
        //     [
        //         'a ',
        //         ' b ',
        //     ],
        //     'a' => [
        //         'a ',
        //         ' b ',
        //     ],
        //     true,
        //     '01 '
        // ];

        if ($this->request->getMethod() === 'GET') {
            Tools::paramsDetectJsonArrays($array);
        }
        Tools::paramsFilter($array);
        Tools::formatJSTimestampToPHPTimestamp($array);
        return $array;
    }
}
