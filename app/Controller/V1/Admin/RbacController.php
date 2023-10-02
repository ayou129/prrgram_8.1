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

namespace App\Controller\V1\Admin;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use App\Middleware\AuthMiddleware;
use App\Model\AdminGroup;
use App\Service\Admin\AdminService;
use App\Service\Business\User\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\Controller;
use Hyperf\HttpServer\Annotation\Middleware;
use Hyperf\HttpServer\Annotation\Middlewares;
use Hyperf\HttpServer\Annotation\PutMapping;
use Hyperf\HttpServer\Annotation\RequestMapping;
use Hyperf\Validation\ValidationException;

/**
 * @Controller
 */
class RbacController extends AbstractController
{
    /**
     * @Inject
     *
     * @var AdminService
     */
    protected $adminService;

    /**
     * @Inject
     *
     * @var UserService
     */
    protected $userService;

    /**
     * @RequestMapping(path="usernameLogin", methods="post")
     */
    public function usernameLogin()
    {
        $validator = $this->validation->make(
            $this->request->all(),
            [
                'username' => 'required',
                'password' => 'required',
            ]
        );
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $tokenInfo = $this->adminService->usernameLogin(
            $this->request->input('username'),
            $this->request->input('password'),
        );

        return $this->responseJson(ServiceCode::SUCCESS, $tokenInfo);
    }

    /**
     * @Middlewares({
     *     @Middleware(AuthMiddleware::class)
     * })
     * @RequestMapping(path="info", methods="get")
     */
    public function info()
    {
        $userModel = getLoginModel('admin');
        $result = $userModel->toArray();
        $result['groups'] = AdminGroup::whereIn('id', $userModel->groups()->pluck('group_id'))
            ->distinct()
            ->pluck('name');
        // $menu_ids = AdminGroupMenusRelation::whereIn('group_id', $userModel->groups()
        //     ->pluck('group_id'))
        //     ->distinct()
        //     ->pluck('menu_id')
        // ;
        // $result = $userModel->toArray();
        // $result['routes'] = AdminMenu::whereIn('id', $menu_ids)
        //     ->where('pid', 0)
        //     ->with([
        //         'children' => function ($query) use ($menu_ids) {
        //             return $query->whereIn('id', $menu_ids);
        //         },
        //     ])
        //     ->orderBy('index', 'desc')
        //     ->get()
        // ;
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    /**
     * @PutMapping(path="password", methods="put")
     */
    public function putPassword()
    {
        $validator = $this->validation->make(
            $this->request->all(),
            [
                'old_password' => 'required',
                'new_password' => 'required',
                're_new_password' => 'required',
            ]
        );
        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
        $userModel = getLoginModel('admin');

        $this->adminService->putPassword(
            $userModel,
            $this->request->input('old_password'),
            $this->request->input('re_new_password'),
        );

        return $this->responseJson(ServiceCode::SUCCESS);
    }
}
