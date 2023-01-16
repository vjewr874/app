<?php
declare (strict_types=1);

namespace app\admin\controller;

use app\common\model\Version;
// use think\Request;
use app\common\model\App as AppModel;

/**
 * 应用类
 * Class App
 * @package app\admin\controller
 */
class App extends BaseController
{
    /**
     * 应用列表
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title('应用列表');
        return view();
    }

    /**
     * 应用创建
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create()
    {
        $this->title('应用创建');
        return view('app_create');
    }

    /**
     * 编辑应用
     * @access public
     * @param $id
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit($id)
    {
        $this->title('编辑应用');
        // 获应用点信息
        $data = AppModel::find($id);
        $this->assign('data', $data);
        return view('app_edit');
    }

    /**
     * 应用版本列表
     * @access public
     * @param $id
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function version($id)
    {
        $this->title('应用版本列表');
        // 获应用信息
        $data = AppModel::find($id);
        $this->assign('data', $data);
        return view('version_list');
    }

    /**
     * 应用版本发布
     * @access public
     * @param $id
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function versionRelease($id)
    {
        $this->title('应用版本发布');
        // 获应用信息
        $data = AppModel::find($id);
        $this->assign('data', $data);
        return view('version_release');
    }

    /**
     * 应用版本编辑
     * @access public
     * @param $id
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function versionEdit($id)
    {
        $this->title('应用版本编辑');
        $data = Version::find($id);
        $this->assign(['data' => $data, 'app' => $data->getApp ?? []]);
        return view('version_edit');
    }
}
