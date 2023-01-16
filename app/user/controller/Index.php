<?php
declare (strict_types=1);

namespace app\user\controller;

use think\Request;

class Index extends BaseController
{
    public $middleware = [
        '\app\middleware\UserAuth' => [
            // 不验证登录列表
            'except' => ['login', 'captcha', 'ajax'],
        ],
    ];

    /**
     * 用户首页
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title('用户首页');
        return view();
    }

    /**
     * 用户登录
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login()
    {
        $this->title('用户登录');
        return view();
    }

    /**
     * 验证码
     * @access public
     * @return \think\Response
     */
    public function captcha()
    {
        return captcha('user_login');
    }
}
