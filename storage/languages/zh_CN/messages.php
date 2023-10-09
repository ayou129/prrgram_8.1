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
return [
    # Base
    'WELCOME' => '欢迎!',
    'SUCCESS' => '操作成功!',
    'ERROR_TOO_MANY_VISITORS' => '访问人数过多，请稍后再试!',

    # Hyperf Exception
    'RuntimeException' => '操作失败!',
    'BadRequestHttpException' => '请求失败!',
    'HttpException' => '请求失败!',
    'ForbiddenHttpException' => '无权访问!',
    'MethodNotAllowedHttpException' => '无权访问该方法!',
    'NotAcceptableHttpException' => '已拒绝接受本数据!',
    'NotFoundHttpException' => '资源不存在!',
    'RangeNotSatisfiableHttpException' => '范围不允许!',
    'ServerErrorHttpException' => '网络繁忙!',
    'UnauthorizedHttpException' => '未经授权，无法操作!',
    'UnprocessableEntityHttpException' => '无法处理该数据!',
    'UnsupportedMediaTypeHttpException' => '不支持的媒体类型!',
    'ServerException' => '网络繁忙!',

    # My System Exception
    'BadGatewayException' => '网关错误!',
    'BadRequestException' => '参数错误!',
    'ForbiddenException' => '无权访问!',
    'GatewayTimeoutException' => '网络超时!',
    'InternalServerErrorException' => '网络错误!',
    'MovedPermanentlyException' => '网址已永久移动!',
    'NotFoundException' => '资源不存在!',
    'RequestTimeoutException' => '请求超时错误!',
    'UnauthorizedException' => '操作未经授权!',

    # 业务
    'ERROR' => '操作失败!',

    'ERROR_MINIPROGRAM_WX_LOGIN_EXPIRE' => '登录失效，请重新登录!',

    # # Params
    'ERROR_PARAM_CLIENT' => '参数错误!',

    # # Admin
    'ERROR_USER_IS_NOT_ADMIN' => '该用户不是管理员',
    'ERROR_USER_IS_NOT_EXISTS' => '该用户不存在',
    'ERROR_MENU_PID_ID_EQUALS' => '上级不能为自己',
    'ERROR_MENU_EXISTS_EQUALS_TITLE' => '存在相同的title!',
    'ERROR_MENU_NOT_EXISTS' => '菜单不存在!',

    'ERROR_DEPT_PID_ID_EQUALS' => '上级不能为自己!',
    'ERROR_DEPT_NOT_EXISTS' => '部门不存在!',

    # # User
    'ERROR_USER_NOT_EXISTS' => '用户不存在!',
    'ERROR_USER_USERNAME_OR_PASSWORD_ERROR' => '用户名或密码错误!',
    'ERROR_USER_EXISTS' => '用户已存在!',
    'ERROR_USER_AUTH_FAIL' => '用户身份有误!',
    'ERROR_USER_ADDRESS_NOT_EXISTS' => '地址不存在!',

    # Z_TAKE_METHOD_FIXEDw report app
    'ERROR_PLAYW_REPORT_CLUB_NOT_EXISTS' => '未绑定俱乐部!',
    'ERROR_PLAYW_REPORT_PLAYW_NAME_NOT_EXISTS' => '未设置陪玩昵称!',
];
