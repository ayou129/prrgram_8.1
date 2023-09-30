<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */
return [
    # Base
    'WELCOME' => 'welcome!',
    'SUCCESS' => 'success!',
    'ERROR_TOO_MANY_VISITORS' => 'Too many visitors, please try again later!',

    # Hyperf Exception
    'RuntimeException' => 'Failed!',
    'BadRequestHttpException' => 'Bad request!',
    'HttpException' => 'Bad request!',
    'ForbiddenHttpException' => 'Forbidden!',
    'MethodNotAllowedHttpException' => 'Method not allowed!',
    'NotAcceptableHttpException' => 'Not acceptable!',
    'NotFoundHttpException' => 'Not found!',
    'RangeNotSatisfiableHttpException' => 'Range not satisfiable!',
    'ServerErrorHttpException' => 'Network busy!',
    'UnauthorizedHttpException' => 'Unauthorized!',
    'UnprocessableEntityHttpException' => 'Unprocessable entity!',
    'UnsupportedMediaTypeHttpException' => 'Unsupported media type!',
    'ServerException' => 'Network busy!',

    # My System Exception
    'BadGatewayException' => 'Bad Gateway Error!',
    'BadRequestException' => 'Bad Request Error!',
    'ForbiddenException' => 'Forbidden Error!',
    'GatewayTimeoutException' => 'Gateway Timeout Error!',
    'InternalServerErrorException' => 'Internal Server Error!',
    'MovedPermanentlyException' => 'Moved Permanently Error!',
    'NotFoundException' => 'Not Found Error!',
    'RequestTimeoutException' => 'Request Timeout Error!',
    'UnauthorizedException' => 'Unauthorized Error!',

    # 业务
    'ERROR' => 'error!',

    # # Params
    'ERROR_PARAM_CLIENT' => 'the param error!',

    # # Admin
    'ERROR_USER_IS_NOT_ADMIN' => 'user is not admin error!',
    'ERROR_USER_IS_NOT_EXISTS' => 'user is not exists error',
    'ERROR_MENU_PID_ID_EQUALS' => 'The menu superior can\'t do it for himself!',
    'ERROR_MENU_EXISTS_EQUALS_TITLE' => 'exists equals title!',
    'ERROR_MENU_NOT_EXISTS' => 'menu data is not exists!',

    'ERROR_DEPT_PID_ID_EQUALS' => 'The dept superior can\'t do it for himself!',
    'ERROR_DEPT_NOT_EXISTS' => 'dept data is not exists!',

    # # User
    'ERROR_USER_NOT_EXISTS' => 'user not exists!',
    'ERROR_USER_USERNAME_OR_PASSWORD_ERROR' => 'username or password error!',
    'ERROR_USER_EXISTS' => 'user exists!',
    'ERROR_USER_AUTH_FAIL' => 'user auth error!',
    'ERROR_USER_ADDRESS_NOT_EXISTS' => 'address not exists!',

    # playw report app
    'ERROR_PLAYW_REPORT_CLUB_NOT_EXISTS' => 'playw is not join club!',
    'ERROR_PLAYW_REPORT_PLAYW_NAME_NOT_EXISTS' => 'playw name is not setting!',
];
