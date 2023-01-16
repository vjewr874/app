<?php
declare (strict_types = 1);

namespace app\admin\controller;

use app\admin\model\AdminUser;
use app\admin\validate\AdminEdit;
use app\admin\validate\AdminLogin;
use app\admin\validate\AppCreate;
use app\admin\validate\AuthCreate;
use app\admin\validate\CardCreate;
use app\admin\validate\NoticeCreate;
use app\admin\validate\UserCreate;
use app\admin\validate\UserEdit;
use app\admin\validate\VersionRelease;
use app\common\controller\Json;
use app\common\model\App as AppModel;
use app\common\model\Auth as AuthModel;
use app\common\model\AuthLog;
use app\common\model\Card as CardModel;
use app\common\model\Log as LogModel;
use app\common\model\Notice;
use app\common\model\User as UserModel;
use app\common\model\Version;
use app\UserError;
use think\exception\ValidateException;
use think\facade\Config;
use think\facade\Db;
use think\facade\Filesystem;
use think\Request;

class Ajax extends BaseController
{
    public $middleware = [
        '\app\middleware\AdminAuth' => [
            // 不验证登录列表
            'except' => ['login'],
        ],
    ];

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
            'captcha'  => $request->param('captcha'),
        ];

        try {
            // 验证数据
            validate(AdminLogin::class)->check($data);
            // 登录
            AdminUser::login($data);
            // 写入Session
            AdminUser::writeSession();
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }

        return Json::success('登录成功')->res();
    }

    /**
     * 获取Menu列表
     * @access public
     * @return array
     */
    public function getMenu()
    {
        return Json::success()->data(Config::get('menu'))->res();
    }

    /**
     * 获取管理员信息
     * @access public
     * @return array
     */
    public function getAdminInfo()
    {
        return Json::success()->data(Config::get('admin_info'))->res();
    }

    /**
     * 修改当前账户信息
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function selfEdit(Request $request)
    {
        // 旧密码
        $oldPassword = $request->param('oldPassword');
        // 新密码
        $password = $request->param('password');

        try {
            // 验证数据
            validate(AdminEdit::class)->check(['oldPassword' => $oldPassword, 'password' => $password]);
            // 保存修改
            $res = AdminUser::find(config('admin_info.id'))->save(['password' => password_hash($password, PASSWORD_DEFAULT)]);

            if ($res) {
                return Json::success()->res();
            }

            ue('修改失败');
        } catch (ValidateException $e) {
            return Json::error($e->getMessage())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 获取控制台统计信息
     * @access public
     * @return mixed
     */
    public function getConsoleInfo()
    {
        // 今日时间戳
        $dayTime = strtotime(date('Y-m-d'));
        // 获取应用数量
        $appNumber = AppModel::count();
        // 获取授权数量
        $authNumber = AuthModel::count();
        // 获取今日新增授权数量
        $authDayNumber = AuthModel::where('create_time', '>', $dayTime)->count();
        // 获取代理数量
        $agentNumber = UserModel::count();
        // 获取卡密数量
        $cardNumber = CardModel::count();
        // 获取已使用卡密数量
        $cardUseNumber = CardModel::where('status', 0)->count();
        // 获取未授权站点数量
        $notAuthNumber = AuthLog::getNotAuthCount();

        return Json::success()->data([
            'appNumber'     => $appNumber,
            'authNumber'    => $authNumber,
            'authDayNumber' => $authDayNumber,
            'agentNumber'   => $agentNumber,
            'cardNumber'    => $cardNumber,
            'cardUseNumber' => $cardUseNumber,
            'notAuthNumber' => $notAuthNumber,
        ])->res();
    }

    /**
     * 获取更新信息
     * @access public
     * @return mixed
     */
    public function getUpdateInfo()
    {
        // try {
        //     // 获取更新列表
        //     list($status, $info) = Helper::getSelfUpdateList();

        //     if (!$status or !is_array($info)) {
        //         ue($info);
        //     }

        //     if (($info['status'] ?? 0) == 0 or !isset($info['data'])) {
        //         ue($info['info'] ?? '云端服务器异常');
        //     }

        //     // 版本列表
        //     $info = $info['data'];

        //     return Json::success()->data($info)->res();
        // } catch (UserError $e) {
        //     return Json::error($e->getMessage())->res();
        // }
        return Json::success()->data([])->res();
    }

    /**
     * 程序自身更新
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function selfUpdate(Request $request)
    {
        // 版本号
        // $number = $request->param('number/d');
        // // 开始更新
        // list($status, $info) = Helper::downloadUpdateFile($number);

        // if (!$status) {
        //     return Json::error($info)->res();
        // }

        // return Json::success($info)->res();

        return Json::success('success')->res();
    }

    /**
     * 文件上传
     * @access public
     * @param Request $request
     * @return array
     */
    public function upload(Request $request)
    {
        // 获取表单上传文件 例如上传了001.jpg
        $file = $request->file('file');
        // 上传类型
        $type = $request->param('type', 'storage');

        if (!in_array($type, ['storage', 'public'])) {
            $type = 'storage';
        }

        try {
            // 验证文件
            validate(['file' => 'fileExt:png,jpg,jpeg,zip'])
                ->check(['file' => $file]);
            // 保存文件
            $saveName = Filesystem::disk($type)->putFile('uploads', $file);

            $obj = Json::success()->data($saveName);

            // 公共存储
            if ($type == 'public') {
                $path = Filesystem::getDiskConfig('public', 'url', '/storage');
                $obj->complete($path . '/' . $saveName);
            }

            return $obj->res();
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        }
    }

    /**
     * 获取服务器信息
     * @access public
     * @return array
     */
    public function getWebsiteInfo()
    {
        // 程序基本信息
        $dataArr = [
            'vn'          => '--',
            'version'     => '--',
            'author'      => '--',
            'programName' => '--',
            'qq'          => '--',
            'url'         => '--',
        ];
        // 合并站点信息
        $dataArr = array_merge($dataArr, Config::get('website', []));

        // 运行目录
        $dataArr['root'] = input('server.DOCUMENT_ROOT');
        // PHP版本
        $dataArr['phpVersion'] = PHP_VERSION;
        // 操作系统
        $dataArr['os'] = PHP_OS;
        // 服务器软件
        $dataArr['software'] = input('server.SERVER_SOFTWARE');
        // 文件上传最大数据
        $dataArr['fileUploadSize'] = (ini_get("file_uploads") ? ini_get("upload_max_filesize") : 0);
        // POST提交最大数据
        $dataArr['postMaxSize'] = (int) ini_get('post_max_size') . 'M';
        // 服务器域名
        $dataArr['domian'] = input('server.HTTP_HOST') . ' / ' . input('server.SERVER_ADDR');
        // 服务器语言
        $dataArr['serverLanguage'] = input('server.HTTP_ACCEPT_LANGUAGE');
        // 脚本运行占用最大内存
        $dataArr['memoryLimit'] = (int) ini_get("memory_limit") . 'M';
        // 脚本最大执行时间
        $dataArr['maxExecutionTime'] = (int) ini_get("max_execution_time") . '秒';

        return Json::success()->data($dataArr)->res();
    }

    /**
     * 获取站点配置
     * @access public
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getSiteSetting()
    {
        // 获取配置
        $data = \app\common\model\Config::getAll();

        return Json::success()->data($data)->res();
    }

    /**
     * 编辑网站配置
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function editSiteSetting(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            \app\admin\model\Config::startTrans();
            // 保存数据
            foreach ($data as $name => $value) {
                \app\admin\model\Config::where(['name' => $name])->update(['value' => $value]);
            }
            \app\admin\model\Config::commit();
            \app\common\model\Config::deleteCache();
            return Json::success()->res();
        } catch (\Exception $e) {
            \app\admin\model\Config::rollback();
            return Json::error('保存数据失败')->res();
        }
    }

    /**
     * 获取应用列表
     * @access public
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function getAppList(Request $request)
    {
        // 每页显示条数
        $number = $request->param('limit');
        // 查询条件
        $where = [];

        // 搜索名称
        $soName = $request->param('soName');
        if (notEmpty($soName)) {
            $where[] = ['name', 'like', '%' . $soName . '%'];
        }

        // 搜索作者名称
        $soAuthor = $request->param('soAuthor');
        if (notEmpty($soAuthor)) {
            $where[] = ['author', 'like', '%' . $soAuthor . '%'];
        }

        // 搜索团队名称
        $soTeamName = $request->param('soTeamName');
        if (notEmpty($soTeamName)) {
            $where[] = ['team_name', 'like', '%' . $soTeamName . '%'];
        }

        // 搜索客服QQ
        $soQQ = $request->param('soQQ');
        if (notEmpty($soQQ)) {
            $where[] = ['qq', 'like', '%' . $soQQ . '%'];
        }

        $list = AppModel::getList($where, ['id', 'desc'], $number);

        return Json::count($list->total())->data($list->items())->res();
    }

    /**
     * 获取所有应用, 默认只包含id,name
     * @access public
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getApp(Request $request)
    {
        $app = AppModel::field('id,name');

        if ($id = $request->param('id')) {
            // 获取单条数据
            $data = $app->field('author,team_name,qq,url,create_time,status')->find($id);
        } else {
            // 获取所有数据
            $data = $app->select();
        }
        return Json::success()->data($data)->res();
    }

    /**
     * 应用创建
     * @access public
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function appCreate(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            // 验证数据
            validate(AppCreate::class)->check($data);
            // 插入数据
            $res = (new AppModel())->save($data);
            if ($res) {
                return Json::success()->res();
            }
            ue('插入数据失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 应用编辑
     * @access public
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function appEdit(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {

            // 验证数据
            validate(AppCreate::class)->check($data);
            // 转到时间戳
            $data['create_time'] = strtotime($data['create_time']);
            // 更新数据
            $res = AppModel::find($data['id'])->save($data);
            if ($res) {
                return Json::success()->res();
            }
            ue('更新数据失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 应用删除
     * @access public
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function appDelete(Request $request)
    {
        // 数据ID列表
        $id = $request->param('id');
        if (!is_array($id)) {
            $id = explode(',', $id);
        }

        // 删除数据
        $res = AppModel::del($id);

        if ($res) {
            return Json::success()->res();
        }
        return Json::error('删除失败')->res();
    }

    /**
     * 获取应用版本列表
     * @access public
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DbException
     */
    public function getAppVersionList(Request $request)
    {
        // 每页显示条数
        $number = $request->param('limit');
        // 查询条件
        $where = ['appid' => $request->param('appid/d')];

        // 搜索版本号
        $soVersion = $request->param('soVersion');
        if (notEmpty($soVersion)) {
            $where[] = ['version', 'like', '%' . $soVersion . '%'];
        }

        // 搜索更新内容
        $soContent = $request->param('soContent');
        if (notEmpty($soContent)) {
            $where[] = ['content', 'like', '%' . $soContent . '%'];
        }

        $list = Version::getList($where, ['id', 'desc'], $number);

        return Json::count($list->total())->data($list->items())->res();
    }

    /**
     * 应用版本发布
     * @access public
     * @param Request $request
     * @return array
     */
    public function appVersionRelease(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {

            // 验证数据
            validate(VersionRelease::class)->check($data);
            // 插入数据
            $res = (new Version())->save($data);
            if ($res) {
                return Json::success()->res();
            }
            ue('插入数据失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 应用版本编辑
     * @access public
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function appVersionEdit(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            if (!isset($data['id'])) {
                ue('版本ID不能为空');
            }
            // 验证数据
            validate(VersionRelease::class)->check($data);
            // 插入数据
            $res = Version::find($data['id'])->save($data);
            if ($res) {
                return Json::success()->res();
            }
            ue('编辑数据失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 应用版本删除
     * @access public
     * @param Request $request
     * @return array
     * @throws \Exception
     */
    public function appVersionDelete(Request $request)
    {
        // 数据ID列表
        $id = $request->param('id');
        if (!is_array($id)) {
            $id = explode(',', $id);
        }

        // 删除数据
        $res = Version::del($id);

        if ($res) {
            return Json::success()->res();
        }
        return Json::error('删除失败')->res();
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
        // 每页显示条数
        $number = $request->param('limit');
        // 查询条件
        $where = [];

        // 搜索应用ID
        $soAppid = $request->param('soAppId');
        if (notEmpty($soAppid)) {
            $where['appid'] = $soAppid;
        }

        // 搜索创建用户ID
        $soCreateId = $request->param('soCreateId');
        if (notEmpty($soCreateId)) {
            $where['create_user'] = $soCreateId;
        }

        // 筛选卡密状态
        $status = $request->param('status');
        if ('' !== $status && !is_null($status)) {
            $where['status'] = $status ? 1 : 0;
        }

        list($count, $data) = CardModel::getList($where, ['id', 'desc'], $number);
        return Json::count($count)->data($data)->res();
    }

    /**
     * 卡密编辑
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cardEdit(Request $request)
    {
        // 卡密ID
        $id = $request->param('id/d');
        // 面值天数
        $day = $request->param('day/d');
        // 失效时间
        $expire_time = $request->param('expire_time/s');

        try {
            if (empty($id)) {
                ue('卡密ID不能为空');
            }
            // 日期转到时间戳
            $expire_time = trim($expire_time) == '0' ? 0 : strtotime($expire_time);

            // 更新数据
            $res = CardModel::find($id)->save(['day' => $day, 'expire_time' => $expire_time]);
            if ($res) {
                return Json::success()->res();
            }
            ue('编辑数据失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 生成卡密
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function cardCreate(Request $request)
    {
        try {
            $data = $request->param();
            // 验证数据
            validate(CardCreate::class)->check($data);

            // 卡密数量
            $number = $data['number'];
            // 每次插入200条数据
            $insertNumber = 200;
            // 值
            $forNumber = ($number / $insertNumber);
            // 循环次数
            $intForNumber = (int) $forNumber;

            $listArray = [];

            // 生成列表数组
            for ($i = 0; $i < $intForNumber; $i++) {
                $listArray[] = $insertNumber;
            }
            // 向上取整
            if (!is_int($forNumber)) {
                $listArray[] = $number - $intForNumber * $insertNumber;
            }

            // 获取管理员信息
            $admin = AdminUser::getLoginInfo();

            // 启动事务
            CardModel::startTrans();

            foreach ($listArray as $value) {
                $insertArray = [];
                for ($i = 1; $i <= $value; $i++) {
                    $insertArray[] = [
                        'card_number' => randomString(4, 4),
                        'appid'       => $data['appid'],
                        'day'         => $data['day'],
                        'expire_time' => $data['expire_time'],
                        'create_name' => '管理员(ID: ' . $admin['username'] . ' )',
                    ];
                }
                (new CardModel())->saveAll($insertArray);
            }

            // 提交事务
            CardModel::commit();

            return Json::success()->res();
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (\Exception $e) {
            // 回滚事务
            CardModel::rollback();
            return Json::error('生成卡密失败')->res();
        }
    }

    /**
     * 卡密删除
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function cardDelete(Request $request)
    {
        // 数据ID列表
        $id = $request->param('id');
        if (!is_array($id)) {
            $id = explode(',', $id);
        }

        // 删除数据
        $res = CardModel::del($id);

        if ($res) {
            return Json::success()->res();
        }
        return Json::error('删除失败')->res();
    }

    /**
     * 获取用户列表
     * @access public
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DbException
     */
    public function getUserList(Request $request)
    {
        // 每页显示条数
        $number = $request->param('limit');
        // 查询条件
        $where = [];

        // 搜索用户ID
        $soUserId = $request->param('soUserId');
        if (notEmpty($soUserId)) {
            $where['id'] = $soUserId;
        }

        // 搜索用户名
        $soUserName = $request->param('soUserName');
        if (notEmpty($soUserName)) {
            $where['username'] = $soUserName;
        }

        // 搜索姓名
        $soName = $request->param('soName');
        if (notEmpty($soName)) {
            $where['name'] = $soName;
        }

        // 搜索QQ
        $soQq = $request->param('soQq');
        if (notEmpty($soQq)) {
            $where['qq'] = $soQq;
        }

        // 筛选用户状态
        $status = $request->param('status');
        if ('' !== $status && !is_null($status)) {
            $where['status'] = $status ? 1 : 0;
        }

        list($count, $data) = UserModel::getList($where, ['id', 'desc'], $number);
        return Json::count($count)->data($data)->res();
    }

    /**
     * 创建用户
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function userCreate(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            // 验证器实例
            $v = validate(UserCreate::class);
            // 设置验证场景
            if ('' === trim($data['qq'])) {
                $data['qq'] = null;
                $v          = $v->scene('NoQQ');
            }
            // 验证数据
            $v->check($data);
            // 密码加密
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            // 插入数据
            $res = (new UserModel())->save($data);
            if ($res) {
                return Json::success()->res();
            }
            ue('写入数据失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 获取用户信息
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserInfo(Request $request)
    {
        // 用户ID
        $id = $request->param('id/d');

        $res = (new UserModel())->find($id);

        if ($res) {
            // 将模型实例转到数组
            $data = $res->toArray();
            // 反序列化
            $data['auth'] = $data['auth'] ? \Opis\Closure\unserialize($data['auth']) : [];
            // 删除密码
            unset($data['password']);

            return Json::success()->data($data)->res();
        }
        return Json::error('用户ID不存在')->res();
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
    public function userEdit(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            // 获取用户信息
            $user = UserModel::find($data['id']);
            if (!$user) {
                ue('编辑的用户不存在');
            }

            // 验证器实例
            $v = validate(UserEdit::class);
            // 设置验证场景
            if ('' === trim($data['qq'])) {
                $data['qq'] = null;
                $v          = $v->scene('NoQQ');
            }
            // 验证数据
            $v->check($data);

            if (!empty($data['password'])) {
                // 密码加密
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            } else {
                unset($data['password']);
            }

            // 修改数据
            $res = $user->save($data);
            if ($res) {
                return Json::success()->res();
            }
            ue('编辑失败');
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
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userEditStatus(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            // 获取用户信息
            $user = UserModel::find($data['id']);
            if (!$user) {
                ue('用户不存在');
            }

            // 修改数据
            $res = $user->save(['status' => $data['status'] ? 1 : 0]);
            if ($res) {
                return Json::success()->res();
            }
            ue('修改失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 用户删除
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function userDelete(Request $request)
    {
        // 数据ID列表
        $id = $request->param('id');
        if (!is_array($id)) {
            $id = explode(',', $id);
        }

        // 删除数据
        $res = UserModel::del($id);

        if ($res) {
            return Json::success()->res();
        }
        return Json::error('删除失败')->res();
    }

    /**
     * 设置用户管理应用权限
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function userAuthEdit(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            // 获取用户信息
            $user = UserModel::find($data['id']);
            if (!$user) {
                ue('用户不存在');
            }
            if (!$data['auth']) {
                ue('请配置应用权限');
            }

            // 序列化
            $auth = \Opis\Closure\serialize($data['auth']);

            // 修改数据
            $res = $user->save(['auth' => $auth]);
            if ($res) {
                return Json::success()->res();
            }
            ue('设置失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 获取授权信息
     * @access public
     * @param Request $request
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getAuth(Request $request)
    {
        // 获取授权信息
        $res = AuthModel::find($request->param('id/d'));

        if ($res) {
            return Json::success()->data($res)->res();
        }

        return Json::error('ID不存在')->res();
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
        // 每页显示条数
        $number = $request->param('limit');
        // 查询条件
        $where = [];

        // 搜索应用ID
        $soAppid = $request->param('soAppId');
        if (notEmpty($soAppid)) {
            $where[] = ['appid', '=', $soAppid];
        }

        // 搜索授权ID
        $soAuthId = $request->param('soAuthId');
        if (notEmpty($soAuthId)) {
            $where[] = ['auth_id', '=', $soAuthId];
        }

        // 搜索用户ID
        $soCreateId = $request->param('soCreateId');
        if (notEmpty($soCreateId)) {
            $where[] = ['create_user', '=', $soCreateId];
        }

        // 搜索用户QQ
        $soQq = $request->param('soQq');
        if (notEmpty($soQq)) {
            $where[] = ['qq', '=', $soQq];
        }

        // 筛选授权状态
        $status = $request->param('status');

        $authModel = AuthModel::where(1);

        if ('' !== $status && !is_null($status)) {
            if ($status == 2) {
                $where[] = ['expire_time', '>', 0];
                $where[] = ['expire_time', '<', time()];
            } else if ($status == 1) {
                $authModel = $authModel->where('expire_time = 0 or expire_time > ' . time());
                $where[]   = ['status', '=', 1];
            } else {
                $where[] = ['status', '=', 0];
            }
        }

        list($count, $data) = AuthModel::getList($where, ['id', 'desc'], $number, $authModel);
        return Json::count($count)->data($data)->res();
    }

    /**
     * 添加授权
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function authCreate(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            // 验证数据
            validate(AuthCreate::class)->check($data);
            // 获取管理员ID
            $id = config('admin_info.id');
            // 创建者
            $data['create_name'] = '管理员(ID: ' . $id . ' )';
            // 插入数据
            $res = (new AuthModel())->save($data);
            if ($res) {
                return Json::success()->res();
            }
            ue('写入数据失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 授权删除
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function authDelete(Request $request)
    {
        // 数据ID列表
        $id = $request->param('id');
        if (!is_array($id)) {
            $id = explode(',', $id);
        }

        // 删除数据
        $res = AuthModel::del($id);

        if ($res) {
            return Json::success()->res();
        }
        return Json::error('删除失败')->res();
    }

    /**
     * 授权编辑
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function authEdit(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            // 获取授权信息
            $auth = AuthModel::find($data['id']);
            if (!$auth) {
                ue('授权信息不存在');
            }

            // 修改数据
            $res = $auth->save($data);
            if ($res) {
                return Json::success()->res();
            }
            ue('设置失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 获取网站日志列表
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function getLogList(Request $request)
    {
        // 每页显示条数
        $number = $request->param('limit');
        // 查询条件
        $where = [];

        // 搜索日志类型
        $soType = $request->param('soType');
        if (notEmpty($soType)) {
            $where[] = ['type', '=', $soType];
        }

        // 搜索操作人ID
        $soUserId = $request->param('soUserId/d');
        if (notEmpty($soUserId)) {
            $where[] = ['user_id', '=', $soUserId];
        }

        list($count, $data) = LogModel::getList($where, ['id', 'desc'], $number);
        return Json::count($count)->data($data)->res();
    }

    /**
     * 获取授权日志列表
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function getAuthLogList(Request $request)
    {
        // 每页显示条数
        $number = $request->param('limit');
        // 查询条件
        $where = [];

        // 搜索类型
        $soType = $request->param('soType');
        if (notEmpty($soType)) {
            $where[] = ['type', '=', $soType];
        }

        // 搜索应用ID
        $soAppId = $request->param('soAppId');
        if (notEmpty($soAppId)) {
            $where[] = ['appid', '=', $soAppId];
        }

        // 搜索授权ID
        $soAuthId = $request->param('soAuthId');
        if (notEmpty($soAuthId)) {
            $where[] = ['auth_id', '=', $soAuthId];
        }

        // 搜索授权内容
        $soAuthContent = $request->param('soAuthContent');
        if (notEmpty($soAuthContent)) {
            $where[] = ['auth_content', '=', $soAuthContent];
        }

        list($count, $data) = AuthLog::getList($where, ['id', 'desc'], $number);
        return Json::count($count)->data($data)->res();
    }

    /**
     * 获取公告列表
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DbException
     */
    public function getNoticeList(Request $request)
    {
        // 每页显示条数
        $number = $request->param('limit');
        // 查询条件
        $where = [];

        // 搜索标题
        $title = $request->param('soTitle/s');
        if (notEmpty($title)) {
            $where[] = ['title', 'like', '%' . $title . '%'];
        }
        list($count, $data) = Notice::getList($where, ['id', 'desc'], $number);
        return Json::count($count)->data($data)->res();
    }

    /**
     * 公告创建
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function noticeCreate(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            // 验证数据
            validate(NoticeCreate::class)->check($data);
            // 顶置
            $data['is_top'] = $data['is_top'] ?? 0 ? 1 : 0;
            // 管理员ID
            $data['uid'] = config('admin_info.id');
            // 插入数据
            $res = (new Notice())->save($data);
            if ($res) {
                return Json::success()->res();
            }
            ue('插入数据失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 公告编辑
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function noticeEdit(Request $request)
    {
        // 获取表单数据
        $data = $request->param();

        try {
            // 验证数据
            validate(NoticeCreate::class)->check($data);
            // 顶置
            $data['is_top'] = $data['is_top'] ?? 0 ? 1 : 0;
            // 修改数据
            $res = Notice::find($data['id'] ?? null)->save($data);
            if ($res) {
                return Json::success()->res();
            }
            ue('修改数据失败');
        } catch (ValidateException $e) {
            return Json::error($e->getError())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 公告删除
     * @access public
     * @param Request $request
     * @return mixed
     * @throws \Exception
     */
    public function noticeDelete(Request $request)
    {
        // 数据ID列表
        $id = $request->param('id');
        if (!is_array($id)) {
            $id = explode(',', $id);
        }

        // 删除数据
        $res = Notice::del($id);

        if ($res) {
            return Json::success()->res();
        }
        return Json::error('删除失败')->res();
    }
}
