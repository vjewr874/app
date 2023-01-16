<?php

namespace app\admin\validate;

use app\common\controller\Helper;
use app\common\model\User;
use think\Validate;

class
UserCreate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'username' => 'require|length:2,20|checkUserName|checkUserNameExists',
        'password' => 'require|length:8,20|checkPassword',
        'name' => 'require|length:2,20',
        'qq' => 'require|number|length:5,10|checkQqExists',
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
        'password.require' => '请输入密码',
        'password.length' => '密码长度8-20位',
        'name.require' => '请输入姓名',
        'name.length' => '姓名长度为2-20位',
        'qq.require' => '请输入QQ号',
        'qq.number' => 'QQ号必须是数字',
        'qq.length' => 'QQ号长度5-10位',
        'auth.require' => '请选择应用代理等级',
    ];

    /**
     * QQ验证场景定义
     * @access public
     * @return UserCreate
     */
    public function sceneNoQQ()
    {
        return $this->remove('qq', true);
    }

    /**
     * 代理场景定义
     * @access public
     * @return UserCreate
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
        // 查询用户名
        $res = User::field('id')->where('qq', trim($value))->find();

        return !$res ? true : '当前QQ号已被绑定';
    }
}
