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

namespace App\Controller\V1;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use App\Exception\ServiceException;
use Hyperf\HttpMessage\Stream\SwooleStream;

class StaticFileController extends AbstractController
{
    /**
     * 获取图片
     * @param $filename
     * @return \Psr\Http\Message\MessageInterface|\Psr\Http\Message\ResponseInterface
     */
    public function file($filename)
    {
        if (! isset($filename)) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
        }
        $file = \Hyperf\Config\config('server.settings.document_root') . DIRECTORY_SEPARATOR . urldecode($filename);
        if (file_exists($file)) {
            $fileContent = file_get_contents($file);
            if ($fileContent === false) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
            }
            return $this->response->withHeader('Content-Type', mime_content_type($file))
                ->withBody(new SwooleStream($fileContent));
        }
        throw new ServiceException(ServiceCode::ERROR_PARAM_CLIENT);
    }
}
