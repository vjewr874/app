<?php

namespace app\admin\validate;

use think\Validate;

class NoticeCreate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'title' => 'require|length:2,255',
        'content' => 'require|length:2,65535',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'title.require' => '请输入标题',
        'title.length' => '标题长度为2-255位',
        'content.require' => '请输入公告内容',
        'content.length' => '公告内容长度为2-65535',
    ];
}
