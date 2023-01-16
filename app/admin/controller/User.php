<?php
declare (strict_types=1);

namespace app\admin\controller;

/**
 * 用户类
 * Class User
 * @package app\admin\controller
 */
class User extends BaseController
{
    /**
     * 用户列表
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title('用户列表');
        return view();
    }

    /**
     * 创建用户
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create()
    {
        $this->title('创建用户');
        return view();
    }

    /**
     * 编辑用户
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit()
    {
        $this->title('编辑用户');
        return view();
    }

    /**
     * 用户授权
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function auth()
    {
        $this->title('用户授权');
        return view();
    }
}
