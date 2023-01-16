<?php

namespace app\common\model;

use app\UserError;
use think\db\exception\DbException;
use think\facade\Session;
use think\Model;
use think\model\concern\SoftDelete;
use think\model\Relation;
use think\exception\ErrorException;

/**
 * Class User
 * @package app\common\model
 */
class User extends Model
{
    use SoftDelete;
    use newPaginate;

    protected $name = 'user';
    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = NULL;
    /**
     * 记录登录后的信息
     * @var array
     */
    protected static $loginParams = [];
    /**
     * Session字段名
     * @var string
     */
    protected static $sessionFiledName = 'user_info';

    /**
     * 获取用户列表
     * @access public
     * @param $where array 条件
     * @param $orderBy array|string 排序
     * @param $number integer 条数
     * @param \think\db\Query|null $authModel
     * @return array
     * @throws DbException
     */
    public static function getList($where, $orderBy, $number, \think\db\Query $authModel = null)
    {
        if (!is_array($orderBy)) {
            $orderBy = [$orderBy];
        }

        if ($number < 10) {
            $number = 10;
        }
        if ($number > 90) {
            $number = 90;
        }

        if (is_null($authModel)) {
            $authModel = new static();
        }

        // 查询结果
        $data = self::newPaginate(
            $authModel->where($where)->order($orderBy[0], $orderBy[1] ?? 'desc'),
            ['list_rows' => $number]
        );

        return [$data->total(), $data->items()];
    }

    /**
     * 获取数据条数
     * @access public
     * @param $where
     * @return int
     */
    public static function getCount($where)
    {
        return self::where($where)->count();
    }

    /**
     * 获取当前登录用户的信息
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

        // 过滤用户信息
        self::filterUserInfo($data);

        // 记录到成员变量中
        self::$loginParams = $data;
        return true;
    }

    /**
     * 过滤用户信息
     * @access public
     * @param array $data
     * @return void
     */
    public static function filterUserInfo(Array &$data)
    {
        // 加密密码
        $data['password'] = md5($data['password']);
    }

    /**
     * 将登录的信息写入到Session中
     * @access protected
     * @param bool $log
     * @return void
     */
    public static function writeSession($log = true)
    {
        if (!empty(self::$loginParams)) {
            // 写Session
            session(self::$sessionFiledName, self::$loginParams);
        }

        // 写用户登录日志
        $log && Log::write([
            'type' => 'user_login',
            'uid' => self::$loginParams['id'],
            'content' => getSessionId(),
        ]);
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
     * 写入登录参数
     * @access protected
     * @param $data
     * @return void
     */
    public static function writeLoginParams($data)
    {
        self::$loginParams = $data;
    }

    /**
     * 用户软删除
     * @access public
     * @param array $id
     * @return bool
     */
    public static function del(Array $id)
    {
        return self::destroy($id);
    }

    public static function getAuth()
    {
        // 获取授权信息
        $data = config('user_info.auth');
        try {
            // 反序列化
            return \Opis\Closure\unserialize($data) ?: [];
        } catch (ErrorException $e) {
            return [];
        }
    }
}
