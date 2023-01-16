<?php

namespace app\install\validate;

use think\Validate;

/**
 * Class ConfigSubmit
 * @package app\admin\validate
 */
class ConfigSubmit extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'db_hostname' => 'require',
        'db_hostport' => 'require|number|between:1,65535',
        'db_username' => 'require',
        'db_password' => 'require',
        'db_database' => 'require',
        'db_prefix' => 'require',
        'username' => 'require|checkUserName',
        'password' => 'require|checkPassword',
        'name' => 'require|length:1,20',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'db_hostname.require' => '请输入数据库地址',
        'db_hostport.require' => '请输入数据库端口',
        'db_hostport.number' => '数据库端口必须是整数',
        'db_hostport.between' => '数据库端口范围1-65535',
        'db_username.require' => '请输入数据库用户名',
        'db_password.require' => '请输入数据库密码',
        'db_database.require' => '请输入数据库名',
        'db_prefix.require' => '请输入数据库前缀',
        'username.require' => '请输入管理员用户名',
        'password.require' => '请输入管理员密码',
        'name.require' => '请输入管理员姓名',
        'name.length' => '管理员姓名长度1-20位',
    ];

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
