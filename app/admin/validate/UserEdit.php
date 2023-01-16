<?php

namespace app\admin\validate;

use app\common\controller\Helper;
use app\common\model\User;
use think\Validate;

class UserEdit extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'username' => 'require|length:2,20|checkUserName|checkUserNameExists',
        'password' => 'checkPassword',
        'name' => 'require|length:2,20',
        'qq' => 'checkQqExists',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'username.require' => '请输入用户名',
        'username.length' => '用户名长度2-20位',
        'name.require' => '请输入姓名',
        'name.length' => '姓名长度为2-20位',
        'auth.require' => '请选择应用代理等级',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        // 编辑个人信息验证场景
        'editUser' => ['name', 'password'],
    ];

    /**
     * QQ验证场景定义
     * @access public
     * @return UserEdit
     */
    public function sceneNoQQ()
    {
        return $this->remove('qq', true);
    }

    /**
     * 代理场景定义
     * @access public
     * @return UserEdit
     */
    public function sceneAgent()
    {
        return $this->remove('qq', true)
            ->append('auth', 'require|checkAuth');
    }

    /**
     * 校验代理授权格式
     * @access protected
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkAuth($value, $rule, $data = [])
    {
        return Helper::checkAuth($value);
    }

    /**
     * 校验用户名
     * @access protected
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkUserName($value, $rule, $data = [])
    {
        return preg_match("/^[0-9A-Za-z]+$/", $value) ? true : '用户名由字母、数字组成';
    }

    /**
     * 校验用户名是否存在
     * @access protected
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function checkUserNameExists($value, $rule, $data = [])
    {
        // 查询用户信息
        $res = $this->getUser($data['id'] ?? null);

        if (!$res) {
            return '编辑的用户不存在';
        }

        if ($res['username'] === $value) {
            return true;
        }

        // 查询用户名
        $res = User::field('id')->where('username', trim($value))->find();

        return !$res ? true : '当前用户名已被占用';
    }

    /**
     * 校验密码
     * @access protected
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkPassword($value, $rule, $data = [])
    {
        // 去掉空格
        $value = trim($value);
        // 空则不验证
        if ('' === $value) {
            return true;
        }

        $count = mb_strlen($value);

        if (!($count >= 8 && $count <= 20)) {
            return '密码长度8-20位';
        }

        return preg_match('/^(?![a-zA-z]+$)(?!\d+$)(?![!@#$%^&*+.]+$)[a-zA-Z\d!@#$%^&*+.]+$/', $value) ? true : '密码由字母+数字或字符组成';
    }

    /**
     * 校验QQ号是否存在
     * @access protected
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function checkQqExists($value, $rule, $data = [])
    {
        // 查询用户信息
        $res = $this->getUser($data['id'] ?? null);

        if (!$res) {
            return '编辑的用户不存在';
        }

        if ($res['qq'] === $value) {
            return true;
        }

        // 查询用户名
        $res = User::field('id')->where('qq', trim($value))->find();

        return !$res ? true : '当前QQ号已被绑定';
    }

    /**
     * 获取用户信息
     * @access public
     * @param Int $id
     * @return array|boolean
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getUser(Int $id)
    {
        // 查询用户信息
        $res = User::find($id);

        return $res ? $res->toArray() : false;
    }
}
