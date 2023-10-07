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

namespace App\Exception;

use App\Constant\ServiceCode;
use App\Utils\Tools;
use Hyperf\Context\Context;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\HttpMessage\Exception\HttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

class Handler extends ExceptionHandler
{
    protected StdoutLoggerInterface $logger;

    /**
     * @Inject
     */
    protected FormatterInterface $formatter;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function handle(Throwable $throwable, ResponseInterface $response): ResponseInterface
    {
        $result['data'] = [];
        $result['code'] = $throwable->getCode() === 0 ? ServiceCode::ERROR : $throwable->getCode();
        $result['msg'] = $throwable->getMessage();
        // var_dump($result['msg'], get_class($throwable));
        // var_dump(Tools::isProduct(), is_subclass_of($throwable, 'LogicException'));
        $httpCode = 400;
        switch (true) {
            case $throwable instanceof ValidationException:
                # hyperf validate exception
                $result['data'] = $throwable->getResponse() ?? [];
                $result['msg'] = $throwable->validator->errors()
                    ->first();
                break;
            case $throwable instanceof HttpException:
                # hyperf http-message exception
                # # 404...
                $result['data'] = method_exists($throwable, 'getResponseData') ? $throwable->getResponseData() : [];
                $httpCode = $throwable->getStatusCode() ?? $httpCode;
                break;
            case $throwable instanceof RuntimeException:
                # hyperf runtime exception: mysql db exception
            case $throwable instanceof LogicException:
            default:
                if (Tools::isProduct()) {
                    $result['msg'] = __('messages.ServerErrorHttpException');
                    $result['code'] = -1;
                }
                $httpCode = 500;
                break;
        }

        if (isset($result['data']) && is_array($result['data'])) {
            asort($result['data']);
        }

        if ($requestLogModel = Context::get('requestLogModel')) {
            $requestLogModel->exception_trace = $throwable->getTraceAsString();
            $requestLogModel->exception_otherinfo = 'Message:' . $throwable->getMessage() . '|Line:' . $throwable->getLine() . '|File:' . $throwable->getFile();
            $requestLogModel->save();
        }

        if (! $result = json_encode($result, JSON_UNESCAPED_UNICODE)) {
            $result = '';
        }

        return $response
            ->withHeader('Content-Type', 'application/json;charset=utf-8')
            ->withStatus($httpCode)
            ->withBody(new SwooleStream($result));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
