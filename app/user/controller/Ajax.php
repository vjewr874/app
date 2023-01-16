<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/11/13 13:03:20
 * 
 */

namespace app\user\controller;


use app\admin\validate\AuthCreate;
use app\admin\validate\CardCreate;
use app\admin\validate\UserCreate;
use app\admin\validate\UserEdit;
use app\common\controller\Helper;
use app\common\model\App as AppModel;
use app\common\model\Auth as AuthModel;
use app\common\model\Card as CardModel;
use app\common\model\Log;
use app\common\model\Notice;
use app\common\model\User as UserModel;
use app\common\model\Version;
use app\user\validate\UserLogin;
use app\common\controller\Json;
use app\UserError;
use think\exception\ValidateException;
use think\facade\Config;
use think\facade\Validate;
use think\model\Relation;
use think\Request;

class Ajax extends BaseController
{
    public $middleware = [
        '\app\middleware\UserAuth' => [
            // 不验证登录列表
            'except' => ['login', 'third'],
        ],
    ];

    /**
     * 分页显示条数
     * @var int
     */
    protected $number = 10;

    /**
     * 获取授权的应用ID
     * @access protected
     * @return array
     */
    protected function _getAppId()
    {
        // 获取授权的应用ID
        return array_keys(UserModel::getAuth());
    }

    /**
     * 获取单条授权信息
     * @access public
     * @param string $id 授权ID
     * @return array|\think\Model|null
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function authFind($id)
    {
        // 获取授权的应用ID
        $app = $this->_getAppId();

        // 获取授权信息
        return AuthModel::where('appid', 'in', $app)->where('create_user', config('user_info.id'))->find($id);
    }

    /**
     * 获取APPID查询条件
     * @access protected
     * @param int|string $appid
     * @return array|void
     */
    protected function getAppidWhere($appid)
    {
        // 应用ID
        $soAppid = $appid;

        // 获取授权的应用ID
        $app = $this->_getAppId();

        if (empty($app) or (notEmpty($soAppid) && !in_array($soAppid, $app))) {
            die(Json::success('可管理应用为空或应用不存在')->count(0));
        }

        // 应用筛选条件
        return notEmpty($soAppid) ? ['appid', '=', $soAppid] : ['appid', 'in', $app];
    }

    /**
     * 登录
     * @access public
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function login(Request $request)
    {
        $data = [
            'username' => $request->param('username'),
            'password' => $request->param('password'),
            'captcha' => $request->param('captcha'),
        ];

        try {
            // 验证数据
            validate(UserLogin::class)->check($data);
            // 登录
            UserModel::login($data);
            // 写入Session
            UserModel::writeSession();
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }

        return Json::success('登录成功')->res();
    }


    public function third(Request $request, $type)
    {
        switch ($type) {
            // 获取QQ登录二维码
            case 'getQQLoginQrcode':
                // 获取二维码信息
                list($status, $info) = Helper::getQrcodeUrl();

                if ($status) {
                    session('third_qq_login', $info['qrsig']);
                    return Json::success()->data($info)->res();
                }

                return Json::error($info)->res();
                break;
            // 获取QQ登录状态
            case 'getQQLoginStatus':
                // 查询sign
                $qrsig = (string)session('third_qq_login');

                if (empty($qrsig)) {
                    return Json::error('请先获取二维码')->res();
                }

                list($status, $info) = Helper::getQrcodeStatus($qrsig);

                // 查询失败
                if (!$status) {
                    return Json::error($info)->res();
                }

                // 登录完成
                if ($info['code'] === 3) {
                    // QQ号
                    $qq = $info['msg'];
                    // 获取用户信息
                    $user = UserModel::where('qq', $qq)->find();

                    if ($user) {
                        $user = $user->toArray();
                        UserModel::filterUserInfo($user);
                        UserModel::writeLoginParams($user);
                        UserModel::writeSession();
                    } else {
                        $info['code'] = 4;
                        $info['msg'] = '当前QQ未绑定';
                    }
                }

                if (in_array($info['code'], [3, -1])) {
                    session('third_qq_login', null);
                }

                return Json::success()->data($info)->res();
                break;
        }
        return Json::error('不存在的操作类型')->res();
    }

    /**
     * 获取控制台统计信息
     * @access public
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getConsoleInfo()
    {
        $authModel = AuthModel::field('id,appid,auth_content,qq,create_time');
        // 用户ID
        $uid = config('user_info.id');
        // 授权的应用数量
        $app = count(UserModel::getAuth());
        // 创建的卡密数量
        $card = CardModel::getCount(['create_user' => $uid]);
        // 授权数量
        list($authCount, $newAuthList) = AuthModel::getList(['create_user' => $uid], ['id', 'desc'], Config::get('config.site_user_auth_number', 15), $authModel);
        // 获取公告
        $noticeList = Notice::field('title,content,create_time')->limit(Config::get('config.site_user_notice_number', 15))->orderRaw('is_top=1,create_time desc')->select();
        // 下级用户数量
        $user = UserModel::getCount(['sid' => $uid]);

        return Json::success()->data(['appNumber' => $app, 'cardNumber' => $card, 'authNumber' => $authCount, 'newAuthList' => $newAuthList, 'userNumber' => $user, 'noticeList' => $noticeList])->res();
    }


    /**
     * 获取账户应用列表
     * @access public
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getApp(Request $request)
    {
        // 获取授权的应用ID
        $app = $this->_getAppId();
        if (empty($app)) {
            return Json::success()->data([])->res();
        }

        if ($request->param('type') == 'view') {
            $data = AppModel::field('id,name,author,team_name,qq,url')->select($this->_getAppId());
            foreach ($data ?: [] as $key => &$value) {
                $value->get_version = Version::field('version,release_time,content')->where('appid', $value->id)->order('id', 'desc')->find();
            }
        } else {
            // 获取应用列表
            $data = AppModel::field('id,name')->select($app);
        }

        return Json::success()->data($data)->agent(Helper::userAgentPurview())->res();
    }

    /**
     * 获取授权列表
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DbException
     */
    public function getAuthList(Request $request)
    {
        // 查询条件
        $where = [];

        // 搜索应用ID
        $soAppid = $request->param('soAppId');

        // 应用筛选条件
        $where[] = $this->getAppidWhere($soAppid);

        // 搜索授权ID
        $soAuthId = $request->param('soAuthId');
        if (notEmpty($soAuthId)) {
            $where[] = ['auth_id', '=', $soAuthId];
        }

        // 搜索授权关键
        $soAuthContent = $request->param('soAuthContent');
        if (notEmpty($soAuthContent)) {
            $where[] = ['auth_content', '=', $soAuthContent];
        }

        // 搜索用户QQ
        $soQq = $request->param('soQq');
        if (notEmpty($soQq)) {
            $where[] = ['qq', '=', (int)$soQq];
        }

        // 筛选授权状态
        $status = $request->param('soStatus');

        $authModel = AuthModel::field('id,appid,auth_content,auth_id,create_time,expire_time,qq,status');

        if ('' !== $status && !is_null($status)) {
            if ($status == 2) {
                $where[] = ['expire_time', '>', 0];
                $where[] = ['expire_time', '<', time()];
            } else if ($status == 1) {
                $authModel = $authModel->where('expire_time = 0 or expire_time > ' . time());
                $where[] = ['status', '=', 1];
            } else {
                $where[] = ['status', '=', 0];
            }
        }

        $where[] = ['create_user', '=', config('user_info.id')];

        list($count, $data) = AuthModel::getList($where, ['id', 'desc'], 10, $authModel);
        return Json::count($count)->data($data)->res();
    }

    /**
     * 添加授权
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function createAuth(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            // 参数过滤
            $data = params(['appid', 'auth_content', 'qq', 'expire_time', 'status', 'create_user', 'create_name'], $data);
            // 验证数据
            validate(AuthCreate::class)->scene('UserAuth')->check($data);
            // 获取用户ID
            $id = config('user_info.id');
            // 创建者ID
            $data['create_user'] = $id;
            // 创建者名称
            $data['create_name'] = '用户(ID: ' . $id . ' )';
            // 插入数据
            $res = (new AuthModel())->save($data);
            if ($res) {
                // 写授权创建日志
                Log::write([
                    'type' => 'create_auth',
                    'uid' => $id,
                    'content' => '应用ID: ' . $data['appid'] . ';授权信息: '
                        . $data['auth_content'] . ';授权QQ: ' . $data['qq']
                        . '到期时间: ' . ($data['expire_time'] ? $data['expire_time'] : '永久')
                ]);

                return Json::success()->res();
            }

            ue('添加失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 编辑授权信息
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editAuth(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            // 参数过滤
            $data = params(['id', 'appid', 'auth_content', 'qq', 'expire_time', 'status'], $data);

            // 验证数据
            validate(AuthCreate::class)->scene('UserAuth')->check($data);

            // 获取授权信息
            $auth = $this->authFind($data['id'] ?? false);
            if (!$auth) {
                ue('授权信息不存在');
            }

            isset($data['status']) && $data['status'] = $data['status'] ? 1 : 0;

            // 修改数据
            $res = $auth->save($data);
            if ($res) {
                // 写授权修改日志
                Log::write([
                    'type' => 'auth_edit',
                    'uid' => config('user_info.id'),
                    'content' => '修改授权,应用ID: ' . $data['appid'] . ';授权信息: '
                        . $data['auth_content'] . ';授权QQ: ' . $data['qq']
                        . '到期时间: ' . ($data['expire_time'] ? $data['expire_time'] : '永久')
                ]);

                return Json::success()->res();
            }
            ue('保存失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 编辑授权状态
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editAuthStatus(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            // 参数过滤
            $data = params(['id', 'status'], $data);

            $data['status'] = $data['status'] ? 1 : 0;

            // 修改数据             // 用户ID
            $res = AuthModel::where('create_user', config('user_info.id'))
                // 应用ID
                ->where('appid', 'in', $this->_getAppId())
                // 要修改的数据ID
                ->where('id', 'in', $data['id'])
                // 修改的状态
                ->update(['status' => $data['status']]);

            // 写授权修改日志
            Log::write([
                'type' => 'auth_edit',
                'uid' => config('user_info.id'),
                'content' => '修改授权ID(' . implode(',', $data['id']) . ') 状态为: ' . $data['status']
            ]);
            return Json::success('操作完成, 成功: ' . (int)$res . '条')->res();
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 获取卡密列表
     * @access public
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function getCardList(Request $request)
    {
        // 搜索应用ID
        $soAppid = $request->param('soAppId');
        // 应用筛选条件
        $where[] = $this->getAppidWhere($soAppid);

        // 搜索卡号
        $soCardNumber = $request->param('soCardNumber');
        if (notEmpty($soCardNumber)) {
            $where[] = ['card_number', '=', $soCardNumber];
        }

        // 筛选卡密状态
        $status = $request->param('soStatus');
        if ('' !== $status && !is_null($status)) {
            $where[] = ['status', '=', $status ? 1 : 0];
        }

        $where[] = ['create_user', '=', config('user_info.id')];

        list($count, $data) = CardModel::getList($where, ['id', 'desc'], $this->number);
        return Json::count($count)->data($data)->res();
    }

    /**
     * 卡密生成
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function createCard(Request $request)
    {
        // 获取表单数据
        $data = $request->param();
        $data = params(['appid', 'number', 'day', 'expire_time'], $data);

        // 卡密模型类
        $cardModel = new CardModel();

        try {
            // 验证数据
            validate(CardCreate::class)->scene('UserAuth')->check($data);

            // 检测应用权限
            if (!Helper::appPurviewQuery($data['appid'], 'create_card')) {
                ue('当前应用未获得创建卡密权限');
            }

            // 卡密数量
            $number = $data['number'];
            // 每次插入200条数据
            $insertNumber = 200;
            // 值
            $forNumber = ($number / $insertNumber);
            // 循环次数
            $intForNumber = (int)$forNumber;

            $listArray = [];

            // 生成列表数组
            for ($i = 0; $i < $intForNumber; $i++) {
                $listArray[] = $insertNumber;
            }
            // 向上取整
            if (!is_int($forNumber)) {
                $listArray[] = $number - $intForNumber * $insertNumber;
            }

            // 用户ID
            $uid = config('user_info.id');

            // 启动事务
            $cardModel->startTrans();

            // ID列表
            $idList = [];

            foreach ($listArray as $value) {
                $insertArray = [];
                for ($i = 1; $i <= $value; $i++) {
                    $insertArray[] = [
                        'card_number' => randomString(4, 4),
                        'appid' => $data['appid'],
                        'day' => $data['day'],
                        'expire_time' => $data['expire_time'],
                        'create_user' => $uid,
                        'create_name' => '用户(ID: ' . $uid . ' )',
                    ];
                }

                $resObj = $cardModel->saveAll($insertArray);

                foreach ($resObj->toArray() as $v) {
                    $idList[] = $v['id'];
                }
            }

            // 提交事务
            $cardModel->commit();

            // 写卡密创建日志
            Log::write([
                'type' => 'create_card',
                'uid' => config('user_info.id'),
                'content' => '在应用(' . $data['appid'] . ') 生成' . $data['number'] . '张卡密, 面值天数: '
                    . $data['day'] . '; 失效时间: ' . ($data['expire_time'] ? date('Y-m-d H:i:s', $data['expire_time']) : $data['expire_time']),
            ]);

            return Json::success()->data($idList)->res();
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (\Exception $e) {
            // 回滚事务
            $cardModel->rollback();
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 卡密编辑
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function editCard(Request $request)
    {
        // 卡密ID
        $id = $request->param('id/d');
        // 面值天数
        $day = $request->param('day/d', 0);
        // 失效时间
        $expire_time = $request->param('expire_time/s', 0);

        try {
            if (empty($id)) {
                ue('卡密ID不能为空');
            }
            // 日期转到时间戳
            $expire_time = trim($expire_time) == '0' ? 0 : strtotime($expire_time);

            // 更新数据             // 用户ID
            $res = CardModel::where('create_user', config('user_info.id'))
                // 应用ID
                ->where('appid', 'in', $this->_getAppId())
                // 要修改的数据ID
                ->where('id', '=', $id)
                // 修改的状态
                ->update(['day' => $day, 'expire_time' => $expire_time]);

            if ($res) {
                // 写卡密编辑日志
                Log::write([
                    'type' => 'card_edit',
                    'uid' => config('user_info.id'),
                    'content' => '修改卡密, ID: ' . $id . '; 面值天数: ' . $day
                        . '; 失效时间' . ($expire_time ? date('Y-m-d H:i:s', $expire_time) : $expire_time),
                ]);
                return Json::success()->res();
            }
            ue('保存失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 导出卡密
     * @access public
     * @param Request $request
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exportCard(Request $request)
    {
        // 数据ID列表
        $id = $request->param('id');
        if ('' !== $id && !is_null($id)) {

            $where[] = ['id', 'in', is_array($id) ? $id : explode(',', $id)];
        }

        // 搜索应用ID
        $soAppid = $request->param('soAppId');
        // 应用筛选条件
        $where[] = $this->getAppidWhere($soAppid);

        // 搜索卡号
        $soCardNumber = $request->param('soCardNumber');
        if (notEmpty($soCardNumber)) {
            $where[] = ['card_number', '=', $soCardNumber];
        }

        // 筛选卡密状态
        $status = $request->param('soStatus');
        if ('' !== $status && !is_null($status)) {
            $where[] = ['status', '=', $status ? 1 : 0];
        }

        $where[] = ['create_user', '=', config('user_info.id')];

        $res = CardModel::field('card_number')->where($where)->select();

        $context = '';
        foreach ($res ?: [] as $v) {
            $context .= implode('-', $v->toArray()) . "\r\n";
        }

        $filename = date('YmdHis') . '-卡密导出';
        header('Content-Type: application/vnd.ms-txt');
        header('Content-Disposition: attachment;filename="' . $filename . '.txt"');
        header('Cache-Control: max-age=0');
        header('Cache-Control: max-age=1');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        header('Cache-Control: cache, must-revalidate');
        header('Pragma: public');
        die($context);
    }

    /**
     * 删除授权
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteAuth(Request $request)
    {
        // 数据ID列表
        $id = $request->param('id');
        if (!is_array($id)) {
            $id = explode(',', $id);
        }

        // 查询数据
        $res = AuthModel::field('id')
            // 用户ID
            ->where('create_user', config('user_info.id'))
            // 应用ID
            ->where('appid', 'in', $this->_getAppId())
            // 要修改的数据ID
            ->where('id', 'in', $id)
            // 执行查询
            ->select();
        if (!$res) {
            return Json::error('授权信息不存在')->res();
        }

        // 删除数据
        $res = AuthModel::del($id);

        if ($res) {
            // 写删除授权日志
            Log::write([
                'type' => 'delete_auth',
                'uid' => config('user_info.id'),
                'content' => '删除授权, ID(' . implode(',', $id) . ')',
            ]);
            return Json::success('删除成功')->res();
        }
        return Json::error('删除失败')->res();
    }

    /**
     * 删除卡密
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteCard(Request $request)
    {
        // 数据ID列表
        $id = $request->param('id');
        if (!is_array($id)) {
            $id = explode(',', $id);
        }

        // 查询数据
        $res = CardModel::field('id')
            // 用户ID
            ->where('create_user', config('user_info.id'))
            // 应用ID
            ->where('appid', 'in', $this->_getAppId())
            // 要修改的数据ID
            ->where('id', 'in', $id)
            // 执行查询
            ->select();
        if (!$res) {
            return Json::error('卡密信息不存在')->res();
        }

        // 删除数据
        $res = CardModel::del($id);

        if ($res) {
            // 写删除卡密日志
            Log::write([
                'type' => 'delete_card',
                'uid' => config('user_info.id'),
                'content' => '删除授权, ID(' . implode(',', $id) . ')',
            ]);
            return Json::success('删除成功')->res();
        }
        return Json::error('删除失败')->res();
    }

    /**
     * 获取用户列表
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DbException
     */
    public function getUserList(Request $request)
    {
        // 查询条件
        $where = [];

        // 搜索用户ID
        $soUserId = $request->param('soUserId');
        if (notEmpty($soUserId)) {
            $where[] = ['id', '=', $soUserId];
        }

        // 搜索用户名
        $soUserName = $request->param('soUserName');
        if (notEmpty($soUserName)) {
            $where[] = ['username', '=', $soUserName];
        }

        // 搜索QQ
        $soQq = $request->param('soQq');
        if (notEmpty($soQq)) {
            $where[] = ['qq', '=', $soQq];
        }

        // 筛选用户状态
        $status = $request->param('status');
        if ('' !== $status && !is_null($status)) {
            $where[] = ['status', '=', $status ? 1 : 0];
        }

        $where[] = ['sid', '=', config('user_info.id')];

        $userModel = (new UserModel())->field('id,username,name,auth,qq,create_time,status');

        list($count, $data) = UserModel::getList($where, ['id', 'desc'], $this->number, $userModel);

        foreach ($data as $k => $v) {
            $data[$k]['auth'] = @unserialize($v['auth']) ?: [];
        }

        return Json::count($count)->data($data)->res();
    }

    /**
     * 创建用户
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DbException
     */
    public function createUser(Request $request)
    {
        // 获取表单数据
        $data = $request->param();
        $data = params(['auth', 'username', 'password', 'name'], $data);

        try {
            // 验证数据
            validate(UserCreate::class)->scene('agent')->check($data);
            // 上级ID
            $data['sid'] = config('user_info.id');
            // 不设置QQ
            $data['qq'] = NULL;
            // 序列化
            $data['auth'] = Helper::filterAuth($data['auth']);
            // 密码加密
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            // 插入数据
            $res = (new UserModel())->save($data);
            if ($res) {
                // 写用户创建日志
                Log::write([
                    'type' => 'create_user',
                    'uid' => config('user_info.id'),
                    'content' => '创建用户, 用户名: ' . $data['username'] . '; 姓名: ' . $data['username'] . '; 权限: ' . $data['auth'],
                ]);
                return Json::success()->res();
            }
            ue('创建失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 用户编辑
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editUser(Request $request)
    {
        // 获取表单数据
        $data = $request->param();
        $data = params(['id', 'auth', 'username', 'password', 'name'], $data);

        try {
            // 获取用户信息
            $user = UserModel::where('sid', config('user_info.id'))->find($data['id']) or ue('编辑的用户不存在');

            // 验证数据
            validate(UserEdit::class)->scene('Agent')->check($data);

            if (!empty($data['password'])) {
                // 密码加密
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            } else {
                unset($data['password']);
            }

            // 序列化
            $data['auth'] = Helper::filterAuth($data['auth']);

            // 修改数据
            $res = $user->save($data);
            if ($res) {
                // 写用户编辑日志
                Log::write([
                    'type' => 'user_edit',
                    'uid' => config('user_info.id'),
                    'content' => '编辑用户, 用户ID: ' . $data['id'] . ';用户名: ' . $data['username'] . '; 姓名: ' . $data['username'] . '; 权限: ' . $data['auth'],
                ]);
                return Json::success()->res();
            }
            ue('保存失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 修改用户状态
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function editUserStatus(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            // 参数过滤
            $data = params(['id', 'status'], $data);

            $data['status'] = $data['status'] ? 1 : 0;

            // 修改数据             // 用户ID
            $res = UserModel::where('sid', config('user_info.id'))
                // 要修改的数据ID
                ->where('id', 'in', $data['id'])
                // 修改的状态
                ->update(['status' => $data['status']]);

            // 写用户编辑日志
            Log::write([
                'type' => 'user_edit',
                'uid' => config('user_info.id'),
                'content' => '修改用户状态, ID(' . $data['id'] . '), 状态: ' . $data['status'],
            ]);
            return Json::success('操作完成, 成功: ' . (int)$res . '条')->res();
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 删除用户
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function deleteUser(Request $request)
    {
        // 数据ID列表
        $id = $request->param('id');
        if (!is_array($id)) {
            $id = explode(',', $id);
        }

        // 查询数据
        $res = UserModel::field('id')
            // 用户ID
            ->where('sid', config('user_info.id'))
            // 要修改的数据ID
            ->where('id', 'in', $id)
            // 执行查询
            ->select();
        if (!$res) {
            return Json::error('用户信息不存在')->res();
        }

        // 删除数据
        $res = UserModel::del($id);

        if ($res) {
            // 写用户删除日志
            Log::write([
                'type' => 'delete_user',
                'uid' => config('user_info.id'),
                'content' => '删除用户, ID(' . implode(',', $id),
            ]);
            return Json::success('删除成功')->res();
        }
        return Json::error('删除失败')->res();
    }

    /**
     * 下载授权文件
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function downloadAuthFile(Request $request)
    {
        // 授权ID
        $id = $request->param('id');
        // 类型
        $type = $request->param('type');

        // 查询授权信息
        $auth = AuthModel::where('appid', 'in', $this->_getAppId())->where('create_user', config('user_info.id'))->find($id);

        if (!$auth) {
            return Json::error('授权信息不存在')->res();
        }

        // 获取文件
        list($status, $info) = Helper::downloadAuthFile($auth, $type == 'update' ? 'update_file' : 'complete_file');

        if (!$status) {
            return Json::error($info)->res();
        }

        return download(Helper::readfileAndDel($info), basename($info), true);
    }

    /**
     * 获取用户信息
     * @access public
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserInfo()
    {
        // 获取用户信息
        $data = UserModel::field('id,username,name,qq,sid,create_time')->find(config('user_info.id'));

        if (!$data) {
            return Json::error('获取用户信息失败')->res();
        }

        return Json::success()->data($data)->res();
    }

    /**
     * 修改用户信息
     * @access public
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editUserInfo(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            switch ($request->param('type')) {
                // 编辑用户信息
                case 'edit':
                    // 参数过滤
                    $data = params(['name', 'password'], $data);
                    // 验证数据
                    validate(UserEdit::class)->scene('editUser')->check($data);

                    if (notEmpty($data['password'])) {
                        // 密码加密
                        $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
                    } else {
                        unset($data['password']);
                    }

                    if (UserModel::find(config('user_info.id'))->save($data)) {
                        // 写用户修改资料日志
                        Log::write([
                            'type' => 'self_edit',
                            'uid' => config('user_info.id'),
                            'content' => '修改资料, 姓名: ' . $data['name'],
                        ]);

                        return Json::success()->res();
                    }
                    ue('保存失败');
                    break;
                case 'untiedQQ':
                    if (UserModel::find(config('user_info.id'))->save(['qq' => null])) {
                        // 写用户修改资料日志
                        Log::write([
                            'type' => 'self_edit',
                            'uid' => config('user_info.id'),
                            'content' => '解绑QQ',
                        ]);

                        return Json::success()->res();
                    }
                    ue('解绑失败');
                    break;
            }
            return Json::error('未定义的操作类型')->res();
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 获取QQ登录二维码信息
     * @access public
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getQrcodeUrl()
    {
        // 获取用户QQ信息
        $qq = UserModel::find(config('user_info.id'))->qq;

        if (!empty($qq)) {
            return Json::error('当前账号已绑定QQ')->res();
        }

        // 获取二维码信息
        list($status, $info) = Helper::getQrcodeUrl();

        if ($status) {
            session('bind_qq', $info['qrsig']);
            return Json::success()->data($info)->res();
        }

        return Json::error($info)->res();
    }

    /**
     * 获取扫码状态
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getQrcodeStatus()
    {

        // 查询sign
        $qrsig = (string)session('bind_qq');

        if (empty($qrsig)) {
            return Json::error('请先获取二维码')->res();
        }

        list($status, $info) = Helper::getQrcodeStatus($qrsig);

        // 查询失败
        if (!$status) {
            return Json::error($info)->res();
        }

        // 登录完成
        if ($info['code'] === 3) {
            // 通过QQ查询用户
            $user = UserModel::where('qq', $info['msg'])->find();

            // QQ已被绑定
            if ($user) {
                $info['code'] = 4;
            } else {
                // 写用户修改资料日志
                Log::write([
                    'type' => 'self_edit',
                    'uid' => config('user_info.id'),
                    'content' => '绑定QQ: ' . $info['msg'],
                ]);

                // 修改QQ号
                UserModel::find(config('user_info.id'))->save(['qq' => $info['msg']]);
            }
        }

        if (in_array($info['code'], [3, -1])) {
            session('bind_qq', null);
        }

        return Json::success()->data($info)->res();
    }
}