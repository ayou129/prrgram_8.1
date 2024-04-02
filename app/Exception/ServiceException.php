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
use Hyperf\HttpMessage\Exception\HttpException;
use ReflectionClass;
use Throwable;

class ServiceException extends HttpException
{
    protected array $response_data = [];

    public function __construct(
        int $const_scene_key,
        array $const_scene_params = [],
        $http_code = 200,
        $response_data = [],
        $custom_msg = '',
        Throwable $previous = null
    ) {
        $this->response_data = $response_data;
        if (! $custom_msg) {
            $class = new ReflectionClass(ServiceCode::class);
            $staticProperties = $class->getConstants();
            foreach ($staticProperties as $propertyName => $value) {
                if ($const_scene_key === $value) {
                    $statusCodeKey = $propertyName;
                    break;
                }
            }
            if (! isset($statusCodeKey)) {
                parent::__construct(500, '', -1, $previous);
                return;
            }
            $message = __('messages.' . $statusCodeKey, $const_scene_params);
        } else {
            $message = $custom_msg;
        }
        parent::__construct($http_code, $message, $const_scene_key, $previous);
    }

    public function getResponseData()
    {
        return $this->response_data;
    }
}
