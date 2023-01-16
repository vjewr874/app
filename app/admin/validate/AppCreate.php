<?php

namespace app\admin\validate;

use think\Validate;

class AppCreate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'name' => 'require|length:2,20',
        'author' => 'require|length:1,10',
        'team_name' => 'require|length:2,20',
        'qq' => 'require|number|length:5,10',
        'url' => 'require|length:4,30',
        'auth_file_name' => 'length:2,100',// require|
        'auth_file_coding' => 'length:2,20',// require|
        'auth_file_content' => 'length:2,65535',// require|
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'name.require' => '请输入应用名称',
        'name.length' => '应用名称长度为2-20位',
        'author.require' => '请输入作者名称',
        'author.length' => '作者名称长度为1-10位',
        'team_name.require' => '请输入团队名称',
        'team_name.length' => '团队名称长度为2-20位',
        'qq.require' => '请输入客服QQ',
        'qq.number' => '客服QQ必须是数字',
        'qq.length' => '客服QQ长度5-10位',
        'url.require' => '请输入官方网址',
        'url.length' => '官方网址长度为4-30位',
        // 'auth_file_name.require' => '授权文件名必填',
        'auth_file_name.length' => '授权文件名必填长度为2-100位',
        // 'auth_file_coding.require' => '授权文件编码必填',
        'auth_file_coding.length' => '授权文件编码长度为2-20位',
        // 'auth_file_content.require' => '授权文件内容必填',
        'auth_file_content.length' => '授权文件内容必填长度为2-65535位',
    ];
}
