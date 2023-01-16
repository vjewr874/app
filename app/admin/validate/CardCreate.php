<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/10/23 23:05:34
 * 
 */

namespace app\admin\validate;


use app\common\model\App as AppModel;
use app\common\model\User as UserModel;
use think\Validate;

/**
 * Class CardCreate
 * @package app\admin\validate
 */
class CardCreate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'appid' => 'require|number|checkAppid',
        'number' => 'require|number|between:1,10000',
        'day' => 'require|number|between:0,5000',
        'expire_time' => 'require|checkExpireTime',
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名'    =>    '错误信息'
     *
     * @var array
     */
    protected $message = [
        'appid.require' => '请选择应用',
        'appid.number' => '应用选择错误',
        'number.require' => '生成数量不能为空',
        'number.number' => '生成数量必须是整数',
        'number.between' => '生成数量范围1-10000张',
        'day.require' => '面值天数不能为空',
        'day.number' => '面值天数必须是整数',
        'day.between' => '面值天数范围0-5000天',
        'expire_time.require' => '到期时间不能为空',
    ];

    /**
     * 用户权限验证场景定义
     * @access public
     * @return CardCreate
     */
    public function sceneUserAuth()
    {
        // 添加用户授权验证
        return $this->append('appid', 'checkUserAuth');
    }

    /**
     * 验证应用ID是否存在
     * @access protected
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkUserAuth($value, $rule, $data = [])
    {
        // 获取当前用户授权的所有应用
        $app = array_keys(UserModel::getAuth());

        return in_array($value, $app) ? true : '无权限控制该应用';
    }

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

    /**
     * 校验到期时间
     * @access protected
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     */
    protected function checkExpireTime($value, $rule, $data = [])
    {
        if ((string)$value === '0') {
            return true;
        }
        return strtotime($value) > time() ? true : '到期时间不能低于当前时间';
    }
}
