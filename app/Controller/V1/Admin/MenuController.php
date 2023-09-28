<?php

declare(strict_types=1);
/**
 * @author liguoxin
 * @email guoxinlee129@gmail.com
 */

namespace App\Controller\V1\Admin;

use App\Constant\ServiceCode;
use App\Controller\AbstractController;
use App\Exception\ServiceException;
use App\Model\BaseModel;
use App\Model\SysMenu;
use App\Model\SysUser;
use Hyperf\HttpServer\Annotation\AutoController;

/**
 * @AutoController
 * Class MenuController
 */
class MenuController extends AbstractController
{
    /**
     * 主页list：默认获取pid为null的.
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function list()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);
        $limit = (int) $this->request->input('size', 10);

        $models = (new SysMenu());
        $models = $models->orderBy('menu_sort');
        $params['sort'] = $this->request->input('sort') ?? [];
        foreach ($params['sort'] as $item) {
            $sort = explode(',', $item);
            $sort_field = $sort[0];
            $sort_type = $sort[1];
            $models = $models->orderBy($sort_field, $sort_type);
        }
        $where = [];
        # pid filter
        if (isset($params['pid'])) {
            if ($params['pid'] == 0) {
                $params['pid'] = null;
            }
            $where[] = [
                'pid',
                $params['pid'],
            ];
        } else {
            $where[] = [
                'pid',
                null,
            ];
        }

        if (isset($params['created_at_start_time'])) {
            $where[] = [
                'created_at',
                '>=',
                $params['created_at_start_time'],
            ];
        }
        if (isset($params['created_at_end_time'])) {
            $where[] = [
                'created_at',
                '<=',
                $params['created_at_end_time'],
            ];
        }
        $result = $models->where($where)->paginate($limit);

        $result = $result->toArray();
        SysMenu::addLabelField($result['data']);
        BaseModel::addTreeFields($result['data']);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    public function create()
    {
        $params = $this->getRequestAllFilter();
        // var_dump($params);

        # 不能重复 title、component_name(name)
        $exists = SysMenu::where('title', '=', $params['title'])
            ->count();
        if ($exists) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
        }

        if ($params['componentName'] !== null) {
            $exists = SysMenu::where('name', '=', $params['componentName'])
                ->count();
            if ($exists) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
            }
        }

        if ($params['pid'] === 0) {
            $params['pid'] = null;
        }
        # 检查一下is_frame HTTP HTTPS
        $model = (new SysMenu());
        $model->pid = $params['pid'];
        $model->sub_count = $params['sub_count'] ?? 0;
        $model->type = $params['type'];
        $model->title = $params['title'];
        $model->name = $params['name'] ?? '';
        $model->component = $params['component'];
        $model->menu_sort = $params['menu_sort'];
        $model->icon = $params['icon'];
        $model->path = $params['path'];
        $model->is_frame = $params['is_frame'];
        $model->cache = $params['cache'];
        $model->hidden = $params['hidden'];
        $model->permission = $params['permission'];
        $model->save();

        # 更新sub_count
        SysMenu::updateAllSubCount();

        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function delete()
    {
        $idsArray = $this->request->all();
        if (! $idsArray || ! is_array($idsArray)) {
            throw new ServiceException(ServiceCode::ERROR_PARAM_FORMAT);
        }
        // var_dump($idsArray,'$idsArray');
        $childMenus = SysMenu::whereIn('pid', $idsArray)
            ->pluck('id');
        $childMenuIds = $childMenus->isEmpty() ? [] : $childMenus->toArray();
        // var_dump($childMenus,'$childMenus');
        $needDeleteIdsArray = array_merge($idsArray, $childMenuIds);

        $needDeleteIdsArray = array_unique($needDeleteIdsArray);

        // var_dump($needDeleteIdsArray,'$needDeleteIdsArray');
        if ($needDeleteIdsArray) {
            SysMenu::updateAllSubCount();
            SysMenu::whereIn('id', $needDeleteIdsArray)
                ->delete();
        }

        SysMenu::updateAllSubCount();
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function put()
    {
        $params = $this->getRequestAllFilter();

        if ($params['id'] === $params['pid']) {
            throw new ServiceException(ServiceCode::ERROR_MENU_PID_ID_EQUALS);
        }

        # Java code
        // Menu menu = menuRepository.findById(resources.getId()).orElseGet(Menu::new);
        // ValidationUtil.isNull(menu.getId(),"Permission","id",resources.getId());

        # 验证is_frame http https
        $model = SysMenu::where('id', '=', $params['id'])
            ->first();
        if (! $model) {
            throw new ServiceException(ServiceCode::ERROR_MENU_NOT_EXISTS);
        }
        // var_dump($menuModel->toArray());
        # 验证title
        $exists = SysMenu::where('title', '=', $params['title'])
            ->where('id', '<>', $params['id'])
            ->count();
        if ($exists) {
            throw new ServiceException(ServiceCode::ERROR_MENU_EXISTS_EQUALS_TITLE);
        }

        if ($params['componentName'] !== null) {
            $exists = SysMenu::where('name', '=', $params['componentName'])
                ->count();
            if ($exists) {
                throw new ServiceException(ServiceCode::ERROR_PARAM_DATA_EXISTS_ERROR);
            }
        }

        if ($params['pid'] === 0) {
            $params['pid'] = null;
        }

        $oldPid = $model->id;
        $newPid = $params['pid'];

        if ($params['pid'] === 0) {
            $params['pid'] = null;
        }
        # 检查一下is_frame HTTP HTTPS
        $model->pid = $params['pid'];
        $model->sub_count = $params['sub_count'];
        $model->type = $params['type'];
        $model->title = $params['title'];
        $model->name = $params['name'] ?? '';
        $model->component = $params['component'];
        $model->menu_sort = $params['menu_sort'];
        $model->icon = $params['icon'];
        $model->path = $params['path'];
        $model->is_frame = $params['is_frame'];
        $model->cache = $params['cache'];
        $model->hidden = $params['hidden'];
        $model->permission = $params['permission'];
        $model->save();

        SysMenu::updateAllSubCount();
        return $this->responseJson(ServiceCode::SUCCESS);
    }

    public function build()
    {
        $token = $this->request->header('Authorization');
        if (! isset($token)) {
            return $this->responseJson(ServiceCode::ERROR_PARAM_MISSING);
        }
        $sysUserModel = SysUser::where('token', $token)
            ->with([
                'roles' => function ($query) {
                    return $query->with([
                        'menus' => function ($query) {
                            return $query->orderBy('menu_sort', 'asc');
                        },
                    ]);
                },
                // 'dept' => function ($query) {
                //     return $query->with(['roles']);
                // },
                // 'jobs'
            ])
            ->first();
        if (! $sysUserModel || ! $sysUserModel->roles) {
            throw new ServiceException(ServiceCode::ERROR_USER_NOT_EXISTS);
        }
        // return $this->responseJson(ServiceCode::SUCCESS, $sysUserModel->toArray());
        // if(!$sysUserModel->roles->menus->isEmpty()){
        // $menusArray = [];
        // foreach ($sysUserModel->roles as $role) {
        //     if ($menus = $role->menus->toarray()) {
        //         $menusArray = array_merge($menusArray, $menus);
        //     }
        // }
        $rolesData = $sysUserModel->roles->toArray();
        $menusData = [];
        foreach ($rolesData as $roleData) {
            if (isset($roleData['menus'])) {
                foreach ($roleData['menus'] as $menu) {
                    # 去重
                    $exist = false;
                    foreach ($menusData as $menuData) {
                        if ($menuData['id'] == $menu['id']) {
                            $exist = true;
                            break;
                        }
                    }
                    if (! $exist) {
                        $menusData[] = $menu;
                    }
                }
            }
        }
        // return $this->responseJson(ServiceCode::SUCCESS, [$menusData]);

        $menusTreeData = self::buildTree($menusData);
        // return $this->responseJson(ServiceCode::SUCCESS, [$menusTreeData]);

        $result = self::buildMenus($menusTreeData);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
        // }
        return $this->responseJson(ServiceCode::SUCCESS, [
            $menusData,
            $result,
        ]);
    }

    public function child()
    {
        $id = $this->request->input('id');
        $modelsPluck = SysMenu::where('pid', $id)
            ->pluck('id');
        $result = $modelsPluck->isEmpty() ? [] : $modelsPluck->toArray();
        $result[] = (int) $id;
        $result = array_unique($result);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    /**
     * 查询菜单:根据ID获取同级与上级数据.
     */
    public function superior()
    {
        // $menuIdsStr = $this->request->input('ids');
        $idsArray = $this->request->all();
        // var_dump($idsArray);

        // if ($menuIdsArray) {
        if (false) {
            // var_dump($idsArray, '$menuIdsArray');
            # in menu_id ids
            # 根据ID获取同级与上级数据
            $menusModels = SysMenu::whereIn('id', $idsArray)
                // ->orderBy($sort_field, $sort_type)
                ->select();
            // $a = [];
            // $menusData = $menusModels->toArray();
            // foreach ($menusData as $item) {
            //     if($item['pid'] === null){
            //         $a[] = $item;
            //     }else{
            //
            //     }
            // }
            //
            // $result = self::buildTree($menusData);
            // return $this->responseJson(ServiceCode::SUCCESS, $result);
            //
            // foreach ($menusModels as $item) {
            //
            // }
            // pid=null的话 orderBy menu_sort
            // 每个菜单
            return $this->responseJson(ServiceCode::SUCCESS, []);
        }
        # 所有顶级菜单
        $result = SysMenu::where('pid', '=', null)
            ->orderBy('id', 'asc')
            ->get();

        $result = $result->toArray();
        SysMenu::addTreeFields($result);
        SysMenu::addLabelField($result);

        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    /**
     * 返回全部的菜单.
     */
    public function lazy()
    {
        $pid = $this->request->input('pid');
        // var_dump($pid);
        if ($pid === '0') {
            $pid = null;
        }
        $result = SysMenu::where('pid', '=', $pid)
            // ->orderBy($sort_field, $sort_type)
            ->get();
        $result = $result->toArray();
        SysMenu::addTreeFields($result);
        SysMenu::addLabelField($result);
        return $this->responseJson(ServiceCode::SUCCESS, $result);
    }

    /**
     * 构建前段用的树状结构.
     */
    public static function buildTree(array $menusData): array
    {
        $trees = $ids = [];
        # 一级菜单
        foreach ($menusData as $menu) {
            if ($menu['pid'] === null) {
                $trees[] = $menu;
            }
        }
        // var_dump($trees);
        foreach ($trees as &$tree) {
            foreach ($menusData as $menu) {
                if ($menu['pid'] == $tree['id']) {
                    if (! isset($tree['children'])) {
                        $tree['children'] = [];
                    }
                    $tree['children'][] = $menu;
                    $ids[] = $menu['id'];
                }
            }
        }
        // if ($trees) {
        //     foreach ($menusData as $menu3) {
        //         if (! in_array($menu3['id'], $ids)) {
        //             $trees[] = $menu3;
        //         }
        //     }
        // }
        return $trees;
    }

    /**
     * 构建菜单.
     * @return array
     */
    public static function buildMenus(array $menusArray)
    {
        $trees = [];
        foreach ($menusArray as $menu) {
            // var_dump($menu);die;

            $menuChildrenList = $menu['children'] ?? [];
            /**
             * 构建前端路由时用到
             * MenuVo['name','path','hidden', 'is_frame'].
             */
            $menuVo = [];
            $menuVo['name'] = $menu['name'] ?: $menu['title'];
            // 一级目录需要加斜杠，不然会报警告
            $menuVo['path'] = $menu['pid'] === null ? '/' . $menu['path'] : $menu['path'];
            $menuVo['hidden'] = $menu['hidden'];
            // 如果不是外链
            if (! $menu['is_frame']) {
                if ($menu['pid'] === null) {
                    if ($menu['component']) {
                        $menuVo['component'] = $menu['component'];
                    } else {
                        $menuVo['component'] = 'Layout';
                    }
                    // 如果不是一级菜单，并且菜单类型为目录，则代表是多级菜单
                } else {
                    if ($menu['component']) {
                        $menuVo['component'] = $menu['component'];
                    }
                    if ($menu['type'] === 0 && ! $menu['component']) {
                        $menuVo['component'] = 'ParentView';
                    }
                }
            }
            // if ($menuVo['component'] === '') {
            //     var_dump($menuVo, $menu);
            //     die;
            // }
            // if (! isset($menu['title'])) {
            // }
            $menuVo['meta'] = [
                'title' => $menu['title'] ?? 'no title',
                'icon' => $menu['icon'] ?? 'no icon',
                'cache' => $menu['cache'] ?? 'no cache',
            ];
            if ($menuChildrenList) {
                $menuVo['alwaysShow'] = true;
                $menuVo['redirect'] = 'noredirect';
                $menuVo['children'] = self::buildMenus($menuChildrenList);
                // 处理是一级菜单并且没有子菜单的情况
            } elseif ($menu['pid'] === null) {
                $menuVo1 = [];
                $menuVo1['meta'] = $menuVo['meta'];
                // 非外链
                if (! $menu['is_frame']) {
                    $menuVo1['path'] = 'index';
                    $menuVo1['name'] = $menuVo['name'];
                    $menuVo1['component'] = $menuVo['component'];
                } else {
                    $menuVo1['path'] = $menu['path'];
                }
                $menuVo['name'] = null;
                $menuVo['meta'] = null;
                $menuVo['component'] = 'Layout';
                $menuVo['children'] = [$menuVo1];
            }
            $trees[] = $menuVo;
        }
        return $trees;
    }
}
