<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/10/23 23:05:34
 * 
 */

namespace app\admin\validate;


use app\common\model\App as AppModel;
use think\Validate;

class VersionRelease extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'appid' => 'require|number|checkAppid',
        'version' => 'require|length:2,20',
        'content' => 'require|length:5,65535',
        'update_file' => 'require|length:5,200',
        'complete_file' => 'require|length:5,200',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'appid.require' => 'APPID不能为空',
        'appid.number' => 'APPID只能是数字',
        'version.require' => '版本号不能为空',
        'version.length' => '版本号长度为2-20位',
        'content.require' => '更新内容不能为空',
        'content.length' => '更新内容长度为5-65535位',
        'update_file.require' => '更新文件压缩包必须上传',
        'update_file.number' => '更新文件压缩包路径为5-200位',
        'complete_file.require' => '完整文件压缩包必须上传',
        'complete_file.length' => '完整文件压缩包路径为5-200位',
    ];

    /**
     * 验证应用ID是否存在
     * @access protected
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function checkAppid($value, $rule, $data = [])
    {
        // 查询应用
        $res = AppModel::field('id')->find($value);
        return $res ? true : '应用ID不存在';
    }
}
