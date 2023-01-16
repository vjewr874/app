<?php
declare (strict_types=1);

namespace app\admin\controller;

/**
 * 授权类
 * Class Auth
 * @package app\admin\controller
 */
class Auth extends BaseController
{
    /**
     * 授权列表
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title('授权列表');
        return view();
    }

    /**
     * 添加授权
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create()
    {
        $this->title('添加授权');
        return view();
    }

    /**
     * 授权编辑
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit()
    {
        $this->title('授权编辑');
        return view();
    }

    /**
     * 授权查询日志
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function log()
    {
        $this->title('查询日志');
        return view();
    }
}
