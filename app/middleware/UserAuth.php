<?php

namespace app\middleware;

use app\common\model\User;
use think\facade\Config;
use think\facade\View;

class UserAuth
{
    /**
     * @access public
     * @param $request
     * @param \Closure $next
     * @return mixed|\think\response\Redirect
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handle($request, \Closure $next)
    {
        // 登录验证
        if (!$this->isLogin()) {
            return redirect(url('user_login'));
        }

        return $next($request);
    }

    /**
     * 验证是否登录
     * @access protected
     * @return boolean
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function isLogin()
    {
        // 获取Session中的信息
        $oldUser = User::getSessionInfo();

        if (empty($oldUser)) {
            return false;
        }

        // 获取当前登录用户的信息
        $newUser = User::getLoginInfo();

        if (isset($oldUser['password']) && isset($newUser['password']) && $oldUser['password'] === md5($newUser['password']) && $newUser['status'] == 1) {

            unset($newUser['password']);
            // 设置用户信息
            Config::set($newUser, 'user_info');

            return true;
        }

        return false;
    }
}
