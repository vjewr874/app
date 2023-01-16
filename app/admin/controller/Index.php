<?php
declare (strict_types=1);

namespace app\admin\controller;

use app\admin\model\AdminUser;
use think\Request;

class Index extends BaseController
{
    public $middleware = [
        '\app\middleware\AdminAuth' => [
            // 不验证登录列表
            'except' => ['login', 'captcha'],
        ],
    ];

    protected function initialize()
    {
    }

    /**
     * 显示资源列表
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title('控制台');
        return view();
    }

    /**
     * 登录页面
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login()
    {
        $this->title('后台登录');
        return view();
    }

    /**
     * 验证码
     * @access public
     * @return \think\Response
     */
    public function captcha()
    {
        return captcha('admin_login');
    }

    /**
     * 后台布局
     * @access public
     * @return \think\response\View
     */
    public function layout()
    {
        return view('public/layout');
    }

    /**
     * 获取模板内容
     * @access public
     * @param $type
     * @param $name
     * @return string|\think\response\View
     */
    public function template($type, $name)
    {
        $file = $type . '/' . $name;
        if (in_array($file, ['error/404', 'error/500'])) {
            return view('public/template/' . $file);
        }

        return '';
    }

    /**
     * 控制台主页
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function home()
    {
        $this->title('控制台');
        return view();
    }

    /**
     * 日志列表
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function log()
    {
        $this->title('日志列表');
        return view();
    }

    /**
     * 注销登录
     * @access public
     * @return string
     */
    public function logout()
    {
        // 删除SESSION
        AdminUser::clearSession();

        return '<script>window.location.href="' . url('admin_login') . '"</script>';
    }

    /**
     * 管理员信息修改
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function edit()
    {
        $this->title('管理员信息修改');
        return view();
    }
}
