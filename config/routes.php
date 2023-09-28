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
use Hyperf\HttpServer\Router\Router;
use App\Controller\V1\Admin\AdminController;
use App\Controller\V1\Admin\ConfigController;
use App\Controller\V1\Admin\DeptController;
use App\Controller\V1\Admin\DictController;
use App\Controller\V1\Admin\DictDetailController;
use App\Controller\V1\Admin\JobController;
use App\Controller\V1\Admin\MenuController;
use App\Controller\V1\Admin\RequestLogController;
use App\Controller\V1\Admin\RoleController;
use App\Controller\V1\Admin\UserController;
use App\Controller\V1\Business\PlaywReport\ClubController;
use App\Controller\V1\Business\PlaywReport\CommonController;
use App\Controller\V1\Business\PlaywReport\PlaywController;
use App\Controller\V1\Business\PlaywReport\UserController as PlaywReportUserController;
use App\Middleware\AuthMiddleware;

// Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');

Router::get('/favicon.ico', function () {
    return '';
});
// Router::get('/', function () {
//     return 'not supported!';
// });
Router::get(
    '/',
    [
        \App\Controller\BaseController::class,
        'index',
    ]
);
Router::addGroup(
    '/v1/admin',
    function () {
        Router::post('/auth/login', [
            AdminController::class,
            'authLogin',
        ]);
        Router::delete('/auth/logout', [
            AdminController::class,
            'authLogout',
        ]);
    },
);

Router::addGroup(
    '/v1/admin',
    function () {
        Router::get('/auth/info', [
            AdminController::class,
            'authInfo',
        ]);
        // ------------------------ Menu -------
        Router::get('/api/menus', [
            MenuController::class,
            'list',
        ]);
        Router::post('/api/menus', [
            MenuController::class,
            'create',
        ]);
        Router::delete('/api/menus', [
            MenuController::class,
            'delete',
        ]);
        Router::put('/api/menus', [
            MenuController::class,
            'put',
        ]);
        Router::get('/api/menus/build', [
            MenuController::class,
            'build',
        ]);
        Router::get('/api/menus/child', [
            MenuController::class,
            'child',
        ]);

        Router::post('/api/menus/superior', [
            MenuController::class,
            'superior',
        ]);

        Router::get('/api/menus/lazy', [
            MenuController::class,
            'lazy',
        ]);

        // ------------------------ User -------
        Router::get('/api/user', [
            UserController::class,
            'list',
        ]);
        Router::put('/api/user', [
            UserController::class,
            'put',
        ]);
        Router::put('/api/user/updateEmail', [
            UserController::class,
            'updateEmail',
        ]);
        Router::put('/api/user/updatePass', [
            UserController::class,
            'updatePass',
        ]);
        Router::post('/api/user', [
            UserController::class,
            'create',
        ]);
        Router::delete('/api/user', [
            UserController::class,
            'delete',
        ]);

        // ------------------------ Dept -------
        Router::get('/api/dept', [
            DeptController::class,
            'list',
        ]);
        Router::get('/api/dept/all', [
            DeptController::class,
            'all',
        ]);
        Router::put('/api/dept', [
            DeptController::class,
            'put',
        ]);
        Router::post('/api/dept', [
            DeptController::class,
            'create',
        ]);
        Router::delete('/api/dept', [
            DeptController::class,
            'delete',
        ]);

        Router::post('/api/dept/superior', [
            DeptController::class,
            'superior',
        ]);

        // ------------------------ Job -------
        Router::get('/api/job', [
            JobController::class,
            'list',
        ]);
        Router::put('/api/job', [
            JobController::class,
            'put',
        ]);
        Router::post('/api/job', [
            JobController::class,
            'create',
        ]);
        Router::delete('/api/job', [
            JobController::class,
            'delete',
        ]);

        // ------------------------ Role -------
        Router::get('/api/roles', [
            RoleController::class,
            'list',
        ]);

        Router::get('/api/roles/id/{id}', [
            RoleController::class,
            'getById',
        ]);
        Router::get('/api/roles/level', [
            RoleController::class,
            'level',
        ]);
        Router::get('/api/roles/all', [
            RoleController::class,
            'all',
        ]);
        Router::put('/api/roles', [
            RoleController::class,
            'put',
        ]);
        Router::put('/api/roles/menu', [
            RoleController::class,
            'putMenu',
        ]);
        Router::post('/api/roles', [
            RoleController::class,
            'create',
        ]);
        Router::delete('/api/roles', [
            RoleController::class,
            'delete',
        ]);

        // ------------------------ Dict -------
        Router::get('/api/dict', [
            DictController::class,
            'list',
        ]);
        Router::put('/api/dict', [
            DictController::class,
            'put',
        ]);
        Router::post('/api/dict', [
            DictController::class,
            'create',
        ]);
        Router::delete('/api/dict', [
            DictController::class,
            'delete',
        ]);

        // ------------------------ DictDetail -------
        Router::get('/api/dictDetail', [
            DictDetailController::class,
            'list',
        ]);
        Router::put('/api/dictDetail', [
            DictDetailController::class,
            'put',
        ]);
        Router::post('/api/dictDetail', [
            DictDetailController::class,
            'create',
        ]);
        Router::delete('/api/dictDetail', [
            DictDetailController::class,
            'delete',
        ]);

        // ------------------------ RequestLog -------
        Router::get('/api/request_log/list', [
            RequestLogController::class,
            'list',
        ]);

        // ------------------------ Config -------
        Router::get('/api/config/list', [
            ConfigController::class,
            'list',
        ]);
        Router::post('/api/config', [
            ConfigController::class,
            'create',
        ]);
        Router::put('/api/config', [
            ConfigController::class,
            'put',
        ]);
        Router::delete('/api/config', [
            ConfigController::class,
            'delete',
        ]);
    },
    ['middleware' => [AuthMiddleware::class]]
);

Router::addGroup(
    '/miniprogram/v1',
    function () {
        Router::put('/test/testPrepare', [
            CommonController::class,
            'testPrepare',
        ]);
        Router::post('/console/log', [
            CommonController::class,
            'postConsoleLog',
        ]);
        Router::get('/options', [
            CommonController::class,
            'getOptions',
        ]);
        // ------------------------ user -------
        Router::post('/user/registerAndLoginByPhone', [
            PlaywReportUserController::class,
            'registerAndLoginByPhone',
        ]);
        //        Router::post('/user/login', [
        //            PlaywReportUserController::class,
        //            'miniLogin',
        //        ]);
        Router::get('/user/check_user_platform_exists', [
            PlaywReportUserController::class,
            'checkUserPlatformExists',
        ]);
        Router::get('/user/playw_report/info', [
            PlaywReportUserController::class,
            'getUserPlaywReportInfo',
        ]);
        Router::put('/user/playw_report/info', [
            PlaywReportUserController::class,
            'putUserPlaywReportInfo',
        ]);

        // ------------------------ User Club -------
        Router::get('/user/club', [
            PlaywController::class,
            'getClubById',
        ]);
        Router::get('/user/club/byname', [
            PlaywController::class,
            'getClubByName',
        ]);
        Router::get('/user/club/ranking', [
            PlaywController::class,
            'getClubRanking',
        ]);
        Router::post('/user/club/join', [
            PlaywController::class,
            'clubJoin',
        ]);
        Router::put('/user/club/leave', [
            PlaywController::class,
            'putClubLeave',
        ]);
        Router::get('/user/order/list', [
            PlaywController::class,
            'getOrderList',
        ]);
        Router::get('/user/club/page/options', [
            PlaywController::class,
            'getClubPageOptions',
        ]);
        Router::get('/user/club/id/status', [
            PlaywController::class,
            'checkClubIdStatus',
        ]);

        // ------------------------ User Boss -------
        Router::post('/user/boss/withAutoApproval', [
            PlaywController::class,
            'postBossWithAutoApproval',
        ]);
        Router::get('/user/club/boss/list', [
            PlaywController::class,
            'getClubBossList',
        ]);
        Router::get('/user/club/boss', [
            PlaywController::class,
            'getClubBoss',
        ]);
        Router::post('/user/club/boss', [
            PlaywController::class,
            'postBossWithAutoApproval',
        ]);
        Router::put('/user/club/boss', [
            PlaywController::class,
            'putClubBoss',
        ]);
        Router::delete('/user/club/boss', [
            PlaywController::class,
            'deleteClubBoss',
        ]);

        // ------------------------ Club Apply -------
        Router::get('/user/club/apply/list', [
            PlaywController::class,
            'getClubApplyList',
        ]);
        Router::get('/user/club/apply', [
            PlaywController::class,
            'getClubApply',
        ]);
        Router::put('/user/club/apply/cancel', [
            PlaywController::class,
            'putClubJoinApplyCancel',
        ]);

        // ------------------------ Order -------
        Router::get('/user/club/order', [
            PlaywController::class,
            'getOrder',
        ]);
        Router::get('/user/club/order/list', [
            PlaywController::class,
            'orderList',
        ]);
        Router::post('/user/club/order', [
            PlaywController::class,
            'orderCreate',
        ]);
        Router::put('/user/club/order', [
            PlaywController::class,
            'putOrder',
        ]);
        Router::delete('/user/club/order', [
            PlaywController::class,
            'deleteOrder',
        ]);
        Router::put('/user/club/order/jqjiezhang', [
            PlaywController::class,
            'putOrderJiezhang',
        ]);
        Router::put('/user/club/order/jqfandian', [
            PlaywController::class,
            'putOrderFandian',
        ]);

        Router::get('/user/club/order/calculate/info', [
            PlaywController::class,
            'orderCalculate',
        ]);

        // ------------------------ Page Data -------
        Router::get('/user/page/index/data', [
            PlaywController::class,
            'getPageIndexData',
        ]);
        Router::get('/user/page/my/data', [
            PlaywController::class,
            'getPageMyData',
        ]);

        // ------------------------ Club Admin playw -------
        Router::get('/user/club/admin/playw/token', [
            PlaywController::class,
            'getClubAdminPlaywToken',
        ]);
        Router::get('/user/club/admin/playw/list/all', [
            PlaywController::class,
            'getClubAdminPlaywListAll',
        ]);
        Router::get('/user/club/admin/playw/list', [
            PlaywController::class,
            'getClubAdminPlaywList',
        ]);
        // ------------------------ Club Admin 任命&移除 -------
        Router::post('/user/club/admin/add', [
            ClubController::class,
            'postClubAdminAdd',
        ]);
        Router::delete('/user/club/admin/delete', [
            ClubController::class,
            'deleteClubAdmin',
        ]);
        Router::post('/user/club/admin/playw/remove', [
            PlaywController::class,
            'clubAdminPlaywRemove',
        ]);
        Router::get('/user/club/admin/setting', [
            PlaywController::class,
            'getClubAdminSetting',
        ]);
        Router::put('/user/club/admin/setting', [
            PlaywController::class,
            'putClubAdminSetting',
        ]);

        // ------------------------ Club Admin group-------
        Router::get('/user/club/admin/group/list', [
            PlaywController::class,
            'getClubAdminGroupList',
        ]);
        Router::post('/user/club/admin/group', [
            PlaywController::class,
            'postClubAdminGroup',
        ]);
        Router::put('/user/club/admin/group', [
            PlaywController::class,
            'putClubAdminGroup',
        ]);
        Router::delete('/user/club/admin/group', [
            PlaywController::class,
            'deleteClubAdminGroup',
        ]);
        // ------------------------ Club Admin Apply -------
        Router::put('/user/club/admin/apply/exec', [
            ClubController::class,
            'putClubAdminApplyExec',
        ]);
        // ------------------------ Club Admin Project -------
        Router::get('/user/club/admin/project/list/all', [
            PlaywController::class,
            'getClubAdminProjectListAll',
        ]);
        Router::get('/user/club/admin/project/list', [
            PlaywController::class,
            'getClubAdminProjectList',
        ]);
        Router::get('/user/club/admin/project', [
            PlaywController::class,
            'getClubAdminProject',
        ]);
        Router::post('/user/club/admin/project', [
            PlaywController::class,
            'postClubAdminProject',
        ]);
        Router::put('/user/club/admin/project', [
            PlaywController::class,
            'putClubAdminProject',
        ]);
        Router::delete('/user/club/admin/project', [
            PlaywController::class,
            'deleteClubAdminProject',
        ]);
        // ------------------------ Club Admin Order -------
        Router::get('/user/club/admin/order/list', [
            ClubController::class,
            'getClubAdminOrderList',
        ]);
        // ------------------------ Club Admin Boss -------
        Router::get('/user/club/admin/boss/list', [
            ClubController::class,
            'getClubAdminBossList',
        ]);

        // ------------------------ Club Admin StairPoint -------
        Router::get('/user/club/admin/stair_point', [
            ClubController::class,
            'getClubAdminStairPoint',
        ]);
        Router::put('/user/club/admin/stair_point', [
            ClubController::class,
            'putClubAdminStairPoint',
        ]);
    }
);