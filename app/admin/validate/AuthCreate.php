<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/10/23 23:05:34
 * 
 */

namespace app\admin\validate;


use app\common\model\App as AppModel;
use app\common\model\Card;
use app\common\model\User as UserModel;
use think\exception\ErrorException;
use think\Validate;

/**
 * Class CardCreate
 * @package app\admin\validate
 */
class AuthCreate extends Validate
{
    /**
     * 定义验证规则
     * 格式：'字段名'    =>    ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'appid' => 'require|number|checkAppid',
        'auth_content' => 'require|length:0,32|checkAuthContent',
        'qq' => 'require|number|length:5,10',
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
        'auth_content.require' => '授权关键不能为空',
        'auth_content.number' => '授权关键长度为0-32位',
        'qq.require' => 'QQ号不能为空',
        'qq.number' => 'QQ号必须是整数',
        'qq.length' => 'QQ号长度5-10位整数',
        'expire_time.require' => '到期时间不能为空',
        'card_number.require' => '卡密不能为空',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        // 只验证APPID
        'onlyVerifyAppid' => ['appid'],
    ];

    /**
     * 用户权限验证场景定义
     * @access public
     * @return AuthCreate
     */
    public function sceneUserAuth()
    {
        // 添加用户授权验证
        return $this->append('appid', 'checkUserAuth');
    }

    /**
     * 在线授权验证场景
     * @access public
     * @return AuthCreate
     */
    public function sceneOnlineAuth()
    {
        return $this->remove('expire_time', true)
            ->append('card_number', 'require|checkCardNumber');
    }

    /**
     * 验证授权卡密
     * @access public
     * @param $value
     * @param $rule
     * @param array $data
     * @return boolean|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function checkCardNumber($value, $rule, $data = [])
    {
        // 获取卡密信息
        $card = Card::where('card_number', $value)
            ->where('appid', $data['appid'])
            ->where('status', 1)
            ->find();

        return $card ? true : '卡密不存在或已使用';
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
     * 验证授权关键
     * @access protected
     * @param $value
     * @param $rule
     * @param array $data
     * @return bool|string
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function checkAuthContent($value, $rule, $data = [])
    {
        // 查询应用
        $reg = AppModel::field('auth_verify_reg')->find($data['appid'])->value('auth_verify_reg');

        // 空则不验证
        if (empty($reg)) {
            return true;
        }

        try {
            return preg_match('#' . $reg . '#', $value) ? true : '授权关键格式验证不通过';
        } catch (ErrorException $e) {
            return '授权关键验证出错';
        }
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
