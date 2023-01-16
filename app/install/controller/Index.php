<?php
declare (strict_types = 1);

namespace app\install\controller;

use app\admin\model\AdminUser;
use app\common\controller\Helper;
use app\common\controller\Json;
use app\install\validate\ConfigSubmit;
use app\UserError;
use think\exception\ValidateException;
use think\facade\Db;
use think\Request;

class Index
{
    public function __construct()
    {
        // 重复安装检测
        if (config('site.install')) {
            exitContent(Json::error('当前网站已安装, 如需重复安装请删除/app/install/install.lock')->res());
        }

        // 当前方法
        $method = request()->action(true);

        // 环境检测
        if (!in_array($method, ['index', 'getserverinfo'])) {
            !Helper::getInstalldetect(true) && exitContent(Json::error('环境检测不通过')->res());
        }

        // 配置检测
        if (in_array($method, ['startinstall'])) {
            !session('install_params') && exitContent(Json::error('请先进行配置后再开始安装')->res());
        }
    }

    /**
     * 程序安装首页
     * @return \think\Response
     */
    public function index()
    {
        return view();
    }

    /**
     * 获取服务器信息
     * @access public
     * @return mixed
     */
    public function getServerInfo()
    {
        return Json::success()->data(Helper::getInstalldetect())->res();
    }

    /**
     * 安装配置提交
     * @access public
     * @param Request $request
     * @return mixed
     */
    public function installConfigSubmit(Request $request)
    {
        // 获取表单信息
        $data = $request->param(['db_hostname', 'db_hostport', 'db_username', 'db_password', 'db_database', 'db_prefix', 'username', 'password', 'name'], null);

        try {
            // 表单验证
            validate(ConfigSubmit::class)->check($data);
            // 数据库连接测试
            $this->db($data['db_hostname'], $data['db_hostport'], $data['db_username'], $data['db_password'], $data['db_database']);
            // 写配置文件
            $this->writeEnvFile($data);
            // 记录管理员信息
            session('install_params', ['db_prefix' => $data['db_prefix'], 'username' => $data['username'], 'password' => $data['password'], 'name' => $data['name']]);
            return Json::success()->res();
        } catch (ValidateException $e) {
            return Json::error($e->getMessage())->res();
        } catch (UserError $e) {
            return Json::error($e->getMessage())->res();
        }
    }

    /**
     * 开始安装
     * @access public
     * @return mixed
     */
    public function startInstall()
    {
        try {
            // 导入数据库
            list($status, $info) = $this->importSql(session('install_params'));
            if (!$status) {
                return Json::error('导入数据库失败, Error: ' . $info)->res();
            }

            // 写入安装锁
            if (!file_put_contents(config('site.installLock'), 'success')) {
                return Json::error('写入安装锁失败, 请检查权限 ')->res();
            }

            return Json::success()->res();
        } catch (\Exception $e) {
        } catch (\ParseError $e) {
        }

        $msg = '安装时出现错误';
        $msg .= ', Error: ' . $e->getMessage() . '<br>';
        $msg .= $e->getFile() . ':' . $e->getLine();

        return Json::error($msg)->res();
    }

    /**
     * 新建数据库实例
     * @access protected
     * @param $host
     * @param $port
     * @param $user
     * @param $pass
     * @param $dbName
     * @return void
     */
    protected function db($host, $port, $user, $pass, $dbName)
    {
        try {
            $dsn  = 'mysql:host=' . $host . ';dbname=' . $dbName . ';port=' . $port . ';charset=utf8';
            $link = new \PDO($dsn, $user, $pass);
            $link = null;
        } catch (\PDOException $e) {
            exitContent(Json::error('数据库连接失败, Error: ' . $e->getMessage())->res());
        }
    }

    /**
     * 写ENV配置文件
     * @access protected
     * @param $config
     * @return void
     * @throws UserError
     */
    protected function writeEnvFile($config)
    {
        $content = <<<TEXT
APP_DEBUG = false

[APP]
DEFAULT_TIMEZONE = Asia/Shanghai

[DATABASE]
TYPE = mysql
HOSTNAME = {$config['db_hostname']}
DATABASE = {$config['db_database']}
USERNAME = {$config['db_username']}
PASSWORD = {$config['db_password']}
HOSTPORT = {$config['db_hostport']}
prefix = {$config['db_prefix']}
CHARSET = utf8
DEBUG = false

[LANG]
default_lang = zh-cn
TEXT;
        !@file_put_contents(ROOT_PATH . '.env', $content) && ue('写入配置文件失败, 请检查权限');
    }

    /**
     * 导入SQL
     * @access protected
     * @param array $params 参数列表
     * @return array
     */
    protected function importSql($params)
    {
        // 获取SQL列表
        $list = require __DIR__ . DS . '..' . DS . 'sql.php';

        try {
            foreach ($list as $sql) {
                foreach ($params as $k => $v) {
                    $sql = str_replace('[' . $k . ']', $v, $sql);
                }

                Db::query($sql);
            }

            // 插入管理员信息
            (new AdminUser())->save(['username' => $params['username'], 'password' => password_hash($params['password'], PASSWORD_DEFAULT), 'name' => $params['name']]);

            return [true, null];
        } catch (\Exception $e) {
            return [false, $e->getMessage()];
        }
    }
}
