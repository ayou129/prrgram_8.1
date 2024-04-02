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

use Hyperf\HttpMessage\Exception\HttpException;
use Throwable;

class RetException extends HttpException
{
    protected array $response_data = [];

    public function __construct(
        $msg = '',
        $const_scene_key = -1,
        $response_data = [],
        Throwable $previous = null
    ) {
        $this->response_data = $response_data;
        parent::__construct(200, $msg, $const_scene_key, $previous);
    }

    public function getResponseData()
    {
        return $this->response_data;
    }
}
