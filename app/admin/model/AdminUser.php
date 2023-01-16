<?php

namespace app\admin\model;

use app\UserError;
use think\Model;

/**
 * Class AdminUser
 * @package app\admin\model
 */
class AdminUser extends Model
{
    public $name = 'admin';
    public $autoWriteTimestamp = true;

    /**
     * 记录登录后的信息
     * @var array
     */
    protected static $loginParams = [];
    /**
     * Session字段名
     * @var string
     */
    protected static $sessionFiledName = 'admin_user';

    /**
     * 获取当前登录管理员的信息
     * @access public
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getLoginInfo()
    {
        // 获取Session用户信息
        $user = self::getSessionInfo();
        // 获取管理员信息
        $data = self::find($user['id']);

        return $data ? $data->toArray() : [];
    }

    /**
     * 获取Session信息
     * @access public
     * @return array
     */
    public static function getSessionInfo()
    {
        return Session(self::$sessionFiledName);
    }

    /**
     * 管理员登录
     * @access public
     * @param array $params 登录参数
     * @return boolean
     * @throws \app\UserError
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function login(Array $params)
    {
        // 获取用户数据
        $data = self::where('username', $params['username'])->find();

        if (!$data) {
            throw new UserError('用户名不存在');
        }
        // 转到数组
        $data = $data->toArray();
        // 密码校验
        if (!password_verify($params['password'], $data['password'])) {
            throw new UserError('密码错误');
        }

        // 加密密码
        $data['password'] = md5($data['password']);
        // 记录到成员变量中
        self::$loginParams = $data;
        return true;
    }

    /**
     * 将登录的信息写入到Session中
     * @access protected
     * @return void
     */
    public static function writeSession()
    {
        if (!empty(self::$loginParams)) {
            // 写Session
            session(self::$sessionFiledName, self::$loginParams);
        }
    }

    /**
     * 清除Session中的登录信息
     * @access protected
     * @return void
     */
    public static function clearSession()
    {
        session(self::$sessionFiledName, null);
    }

    /**
     * 管理员创建
     * @access public
     * @param array $params 参数
     * @return boolean|integer
     */
    public static function createUser(Array $params)
    {
        // 明文加密
        $params['password'] = password_hash($params['password'], PASSWORD_DEFAULT);
        // 插入数据
        $result = self::create($params);

        if ($result) {
            return $result->id;
        }
        return false;
    }
}
