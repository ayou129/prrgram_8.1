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

namespace App\Constant;

use Hyperf\Constants\AbstractConstants;
use Hyperf\Constants\Annotation\Constants;

/**
 * @method getMessage(int $statusCode, array $statusCodeParams = [])
 */
#[Constants]
class ServiceCode extends AbstractConstants
{
    # Base
    public const WELCOME = 1;

    public const SUCCESS = 0;

    public const ERROR = -1;

    public const ERROR_PARAM_CLIENT = -2;

    # Base -10****
    public const ERROR_TOO_MANY_VISITORS = -100001;

    # Miniprogram -12****
    public const ERROR_MINIPROGRAM_WX_LOGIN_EXPIRE = -120001;

    # Admin -11****
    public const ERROR_USER_IS_NOT_ADMIN = -11001;

    public const ERROR_USER_IS_NOT_EXISTS = -11002;

    public const ERROR_MENU_PID_ID_EQUALS = -11003;

    public const ERROR_MENU_EXISTS_EQUALS_TITLE = -11004;

    public const ERROR_MENU_NOT_EXISTS = -11005;

    public const ERROR_DEPT_PID_ID_EQUALS = -12003;

    public const ERROR_DEPT_NOT_EXISTS = -12005;

    # Business
    # # User *****
    public const ERROR_USER_FAIL = -200001;

    public const ERROR_USER_AUTH_FAIL = -200002;

    public const ERROR_USER_NOT_EXISTS = -200010;

    public const ERROR_USER_EXISTS = -200011;

    public const ERROR_USER_USERNAME_OR_PASSWORD_ERROR = -200020;

    # User Address 21****
    public const ERROR_USER_ADDRESS_NOT_EXISTS = -210001;

    public const ERROR_USER_ADDRESS_REPEAT = -210002;

    # Order 51****
    public const SUCCESS_ORDER_PAID = 500001;

    public const ERROR_ORDER_NOT_EXISTS = -500001;

    public const ERROR_PLAYW_REPORT_CLUB_NOT_EXISTS = -900001;

    public const ERROR_PLAYW_REPORT_PLAYW_NAME_NOT_EXISTS = -900002;
}
