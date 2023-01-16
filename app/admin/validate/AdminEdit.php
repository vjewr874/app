<?php

namespace app\admin\validate;

use app\admin\model\AdminUser;
use think\Validate;

class AdminEdit extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'oldPassword' => 'require|checkOldPassword',
        'password' => 'require|length:8,20|checkPassword',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'oldPassword.require' => '请输入旧密码',
        'password.require' => '请输入新密码',
        'password.length' => '新密码长度8-20位',
    ];

    /**
     * 校验旧密码是否正确
     * @access [access]
     * @param $value
     * @param $rule
     * @param array $data
     * @return string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function checkOldPassword($value, $rule, $data = [])
    {
        // 获取管理员信息
        $admin = AdminUser::find(config('admin_info.id'));

        if (!$admin) {
            return '管理员信息不存在';
        }

        return password_verify($value, $admin->password) ?: '旧密码错误';
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
}
