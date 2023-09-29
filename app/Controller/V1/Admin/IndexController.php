<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Controller\V1\Admin;

use App\Constant\ServiceCode;
use App\Controller\BaseController;
use App\Service\Business\User\UserService;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpServer\Annotation\AutoController;
use Hyperf\View\RenderInterface;


class IndexController extends BaseController
{
    /**
     * @Inject
     *
     * @var UserService
     */
    protected $userService;

    public function index2(RenderInterface $render)
    {
        return $render->render('index.tpl', [
            'title' => 'title1',
            'name' => 'name1',
        ]);
    }

    public function userList()
    {
        $userModel = getLoginModel('admin');
        $result = $this->userService->getList($this->request->all());

        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }
}
