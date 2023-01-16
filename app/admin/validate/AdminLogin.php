<?php

namespace app\admin\validate;

use think\Validate;

class AdminLogin extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'captcha' => 'checkCaptcha',
        'username' => 'require',
        'password' => 'require',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'username.require' => '请输入用户名',
        'password.require' => '请输入密码',
    ];

    // 验证图形验证码
    protected function checkCaptcha($value, $rule, $data = [])
    {
        return captcha_check($value) ? true : '验证码错误';
    }
}
