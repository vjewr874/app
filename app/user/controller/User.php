<?php
declare (strict_types=1);

namespace app\user\controller;

use think\Request;

/**
 * Class User
 * @package app\user\controller
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
     * 个人信息
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function info()
    {
        $this->title('个人信息');
        return view();
    }

    public function logout()
    {
        // 删除SESSION
        \app\common\model\User::clearSession();

        return '<script>window.location.href="' . url('user_login') . '"</script>';
    }
}
