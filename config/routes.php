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
use App\Controller\V1\Business\Wuliu\Bill\BillController;
use App\Controller\V1\Business\Wuliu\Chewu\CarController;
use App\Controller\V1\Business\Wuliu\Chewu\DriverController;
use App\Controller\V1\Business\Wuliu\Chewu\MotorcadeController;
use App\Controller\V1\Business\Wuliu\Chewu\PartnerController;
use App\Controller\V1\Business\Wuliu\Chewu\ShipCompanyController;
use App\Controller\V1\Business\Wuliu\IndexController;
use App\Controller\V1\Business\Wuliu\SeaWaybill\JinkouController;
use App\Controller\V1\Business\Wuliu\SeaWaybill\SailScheduleController;
use App\Controller\V1\Business\Wuliu\SeaWaybill\SeaWaybillController;
use App\Middleware\AuthMiddleware;
use Hyperf\HttpServer\Router\Router;

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
    '/test',
    function () {
        Router::get('/exception', [
            \App\Controller\TestController::class,
            'testException',
        ]);
    }
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
    '/api/v1/admin/wuliu',
    function () {
        // ------------------------ Base -------
        Router::get('/api/index/data', [
            IndexController::class,
            'data',
        ]);
        // ------------------------ Partner -------
        Router::get('/api/partner/all', [
            PartnerController::class,
            'all',
        ]);
        Router::get('/api/partner/list', [
            PartnerController::class,
            'list',
        ]);
        Router::post('/api/partner', [
            PartnerController::class,
            'post',
        ]);
        Router::put('/api/partner', [
            PartnerController::class,
            'put',
        ]);
        Router::delete('/api/partner', [
            PartnerController::class,
            'delete',
        ]);

        // ------------------------ ShipCompany -------
        Router::get('/api/ship_company/all', [
            ShipCompanyController::class,
            'all',
        ]);
        Router::get('/api/ship_company/list', [
            ShipCompanyController::class,
            'list',
        ]);
        Router::post('/api/ship_company', [
            ShipCompanyController::class,
            'post',
        ]);
        Router::put('/api/ship_company', [
            ShipCompanyController::class,
            'put',
        ]);
        Router::delete('/api/ship_company', [
            ShipCompanyController::class,
            'delete',
        ]);

        // ------------------------ Car -------
        Router::get('/api/car/search_options', [
            CarController::class,
            'searchOptions',
        ]);
        Router::get('/api/car/all', [
            CarController::class,
            'all',
        ]);
        Router::get('/api/car/list', [
            CarController::class,
            'list',
        ]);
        Router::post('/api/car', [
            CarController::class,
            'post',
        ]);
        Router::put('/api/car', [
            CarController::class,
            'put',
        ]);
        Router::delete('/api/car', [
            CarController::class,
            'delete',
        ]);
        // ------------------------ Motorcade -------
        Router::get('/api/motorcade/search_options', [
            MotorcadeController::class,
            'searchOptions',
        ]);
        Router::get('/api/motorcade/all', [
            MotorcadeController::class,
            'all',
        ]);
        Router::get('/api/motorcade/list', [
            MotorcadeController::class,
            'list',
        ]);
        Router::post('/api/motorcade', [
            MotorcadeController::class,
            'post',
        ]);
        Router::put('/api/motorcade', [
            MotorcadeController::class,
            'put',
        ]);
        Router::delete('/api/motorcade', [
            MotorcadeController::class,
            'delete',
        ]);

        // ------------------------ Driver -------
        Router::get('/api/driver/all', [
            DriverController::class,
            'all',
        ]);
        Router::get('/api/driver/list', [
            DriverController::class,
            'list',
        ]);
        Router::post('/api/driver', [
            DriverController::class,
            'post',
        ]);
        Router::put('/api/driver', [
            DriverController::class,
            'put',
        ]);
        Router::delete('/api/driver', [
            DriverController::class,
            'delete',
        ]);

        // ------------------------ SeaWaybill -------
        Router::get('/api/sea_waybill/search_options', [
            SeaWaybillController::class,
            'searchOptions',
        ]);
        Router::get('/api/sea_waybill/list', [
            SeaWaybillController::class,
            'list',
        ]);
        Router::post('/api/sea_waybill', [
            SeaWaybillController::class,
            'post',
        ]);
        Router::put('/api/sea_waybill', [
            SeaWaybillController::class,
            'put',
        ]);
        Router::delete('/api/sea_waybill', [
            SeaWaybillController::class,
            'delete',
        ]);
        Router::post('/api/sea_waybill/copy', [
            SeaWaybillController::class,
            'copy',
        ]);
        Router::post('/api/sea_waybill/paiche', [
            SeaWaybillController::class,
            'paiche',
        ]);
        Router::post('/api/sea_waybill/paiche/cancel', [
            SeaWaybillController::class,
            'paicheCancel',
        ]);
        Router::post('/api/sea_waybill/bill/luru', [
            SeaWaybillController::class,
            'billLuru',
        ]);
        Router::post('/api/sea_waybill/bill/luru/cancel', [
            SeaWaybillController::class,
            'billLuruCancel',
        ]);
        Router::post('/api/sea_waybill/partner/bind', [
            SeaWaybillController::class,
            'bindPartner',
        ]);
        Router::post('/api/sea_waybill/partner/bind/cancel', [
            SeaWaybillController::class,
            'bindPartnerCancel',
        ]);

        Router::get('/api/sea_waybill/port/select', [
            SeaWaybillController::class,
            'zjportSelect',
        ]);
        Router::post('/api/sea_waybill/import/men', [
            SeaWaybillController::class,
            'importMen',
        ]);
        Router::post('/api/sea_waybill/import/shoudongdan', [
            SeaWaybillController::class,
            'importShoudongdan',
        ]);
        // Router::post('/api/sea_waybill/import', [
        //     SeaWaybillController::class,
        //     'importCommon',
        // ]);
        // Router::post('/api/sea_waybill/import/temp1', [
        //     SeaWaybillController::class,
        //     'importTemp1',
        // ]);
        // Router::post('/api/sea_waybill/import/temp2', [
        //     SeaWaybillController::class,
        //     'importTemp2',
        // ]);
        // Router::post('/api/sea_waybill/import/zhongguangshimo2022', [
        //     SeaWaybillController::class,
        //     'importZhongguangshimo2022',
        // ]);
        // Router::post('/api/sea_waybill/import/guanjian20212022', [
        //     JinkouController::class,
        //     'importGuanjian20212022',
        // ]);
        // Router::post('/api/sea_waybill/import/haihang2022', [
        //     JinkouController::class,
        //     'importHaihang2022',
        // ]);
        // Router::post('/api/sea_waybill/import/new_common', [
        //     JinkouController::class,
        //     'importNewCommon',
        // ]);
        // Router::post('/api/sea_waybill/import/new_common_add_partner_id', [
        //     JinkouController::class,
        //     'importNewCommonAddPartnerId',
        // ]);
        // Router::post('/api/sea_waybill/import/antong/jinkou', [
        //     SeaWaybillController::class,
        //     'importAntongJinkou',
        // ]);
        // Router::post('/api/sea_waybill/jinkou/zhonggu/import', [
        //     JinkouController::class,
        //     'importZhongGuJinkou',
        // ]);
        // Router::post('/api/sea_waybill/chukou/zhonggu/teshu/import', [
        //     JinkouController::class,
        //     'importZhongguTeshupaicheChukou',
        // ]);
        // Router::post('/api/sea_waybill/jinkou/antong/import', [
        //     JinkouController::class,
        //     'importAntongJinkou',
        // ]);
        // Router::post('/api/sea_waybill/jinkou/zhongyuan/import', [
        //     JinkouController::class,
        //     'importZhongyuanJinkou',
        // ]);
        // Router::post('/api/sea_waybill/jinkou/import/tencent/zhonggu2023', [
        //     JinkouController::class,
        //     'importTencentZhonggu2023',
        // ]);
        // Router::post('/api/sea_waybill/jinkou/import/tencent/zhonggu2023/fix_exists_data', [
        //     JinkouController::class,
        //     'importTencentZhonggu2023FixExistsData',
        // ]);
        // Router::post('/api/sea_waybill/jinkou/import/tencent/antongdaomen2023', [
        //     JinkouController::class,
        //     'importTencentAntongDaomen2023',
        // ]);
        // Router::post('/api/sea_waybill/jinkou/import/antongsystem/cydaogang', [
        //     JinkouController::class,
        //     'importAntongSystemCYDaoGang',
        // ]);

        // ------------------------ SailSchedule -------
        Router::get('/api/sail_schedule/list', [
            SailScheduleController::class,
            'list',
        ]);
        Router::post('/api/sail_schedule', [
            SailScheduleController::class,
            'post',
        ]);
        Router::put('/api/sail_schedule', [
            SailScheduleController::class,
            'put',
        ]);
        Router::delete('/api/sail_schedule', [
            SailScheduleController::class,
            'delete',
        ]);

        // ------------------------ Bill -------
        Router::post('/api/bill/export', [
            BillController::class,
            'export',
        ]);
        Router::post('/api/sea_waybill/download/receipt', [
            SeaWaybillController::class,
            'downloadReceipt',
        ]);
        Router::get('/api/bill/options', [
            BillController::class,
            'optons',
        ]);
        Router::get('/api/bill/list', [
            BillController::class,
            'list',
        ]);
        Router::post('/api/bill', [
            BillController::class,
            'post',
        ]);
        Router::put('/api/bill', [
            BillController::class,
            'put',
        ]);
        Router::put('/api/bill/status', [
            BillController::class,
            'putStatus',
        ]);
        Router::delete('/api/bill', [
            BillController::class,
            'delete',
        ]);
    },
    ['middleware' => [AuthMiddleware::class]]
);

// ------------------------ Template -------
// Router::addGroup(
//     '/api/v1/admin/wuliu',
//     function () {
//         // ------------------------ SailSchedule -------
//         Router::get('/api/model/list', [
//             ModelController::class,
//             'list',
//         ]);
//         Router::post('/api/sail_schedule', [
//             ModelController::class,
//             'post',
//         ]);
//         Router::put('/api/sail_schedule', [
//             ModelController::class,
//             'put',
//         ]);
//         Router::delete('/api/sail_schedule', [
//             ModelController::class,
//             'delete',
//         ]);
//     },
//     // ['middleware' => [AuthMiddleware::class]]
// );
