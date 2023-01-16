<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/11/12 12:39:18
 * 
 */

namespace app\common\controller;

use app\common\model\App;
use app\common\model\User as UserModel;
use think\facade\Filesystem;
use think\Model;
use think\model\Relation;
use ZipArchive;

class Helper
{
    /**
     * 获取应用对应的代理ID
     * @access public
     * @param $appid
     * @return mixed
     */
    public static function getAppAgentId($appid)
    {
        // 获取用户授权的应用列表
        $app = UserModel::getAuth();

        return $app[$appid] ?? false;
    }

    /**
     * App权限范围查询
     * @access public
     * @param integer $appid 应用ID
     * @param string $purview 权限名称
     * @return boolean
     */
    public static function appPurviewQuery($appid, $purview)
    {
        // 获取代理ID
        if (false === ($agentId = self::getAppAgentId($appid))) {
            return false;
        }

        // 获取权限范围
        $data = config('agent.' . $agentId . '.purview') ?: [];

        return in_array($purview, $data);
    }

    /**
     * 获取用户创建代理权限
     * @access protected
     * @return mixed
     */
    public static function userAgentPurview()
    {
        // 获取应用列表
        $app = UserModel::getAuth();
        // 获取代理配置
        $agent = config('agent');

        $res = [];

        // 遍历应用列表
        foreach ($app as $appId => $agentId) {
            $res[$appId] = [];
            // 获取代理创建等级
            foreach ($agent[$agentId]['params']['create'] ?? [] as $cid) {
                
                if (!isset($agent[$cid]['name'])) {
                    continue;
                }
                $res[$appId][] = [
                    'id'   => $cid,
                    'name' => $agent[$cid]['name'],
                ];
            }
        }

        return $res;
    }

    /**
     * 校验代理授权格式
     * @access protected
     * @param $value
     * @return bool|string
     */
    public static function checkAuth($value)
    {
        // 获取添加代理权限列表
        $agent = config('agent');

        if (empty($value)) {
            return '请设置用户应用代理等级';
        }
        if (!is_array($value)) {
            return '用户等级格式错误';
        }

        foreach ($value as $appid => $agentId) {
            // 权限验证
            if (!Helper::appPurviewQuery($appid, 'create_agent')) {
                return '您当前在应用(' . $appid . ')没有权限创建用户';
            }

            if (!notEmpty($agentId)) {
                continue;
            }

            if (!isset($agent[$agentId])) {
                return '应用(' . $appid . ')选择的代理等级不存在';
            }

            // 用户应用代理ID
            $userAgentId = Helper::getAppAgentId($appid);

            // 创建者该应用权限
            $appCreateId = $agent[$userAgentId]['params']['create'];

            if (!in_array($agentId, $appCreateId)) {
                return '您当前在应用(' . $appid . ')不能创建' . $agent[$agentId]['name'];
            }
        }
        return true;
    }

    /**
     * 过滤用户编辑应用授权信息
     * @access public
     * @param $value
     * @return string
     */
    public static function filterAuth($value)
    {
        $res = [];
        foreach ($value ?: [] as $k => $v) {
            if ('' === trim($v)) {
                continue;
            }
            $res[(integer) $k] = (integer) $v;
        }
        return \Opis\Closure\serialize($res);
    }

    /**
     * 获取安装环境检测结果
     * @access public
     * @param bool $bool
     * @return array|boolean
     */
    public static function getInstalldetect($bool = false)
    {
        $data = [];

        // PHP版本校验
        $data[] = ['name' => 'PHP版本', 'current' => PHP_VERSION, 'demand' => '7.1.0', 'status' => version_compare(PHP_VERSION, '7.1.0', '>')];
        $data[] = ['name' => '类', 'current' => '--', 'demand' => 'pdo', 'status' => class_exists('PDO')];
        $data[] = ['name' => '类', 'current' => '--', 'demand' => 'ZipArchive', 'status' => class_exists('ZipArchive')];
        $data[] = ['name' => '函数', 'current' => '--', 'demand' => 'curl', 'status' => function_exists('curl_exec')];
        // $data[] = ['name' => '函数', 'current' => '--', 'demand' => 'asda', 'status' => function_exists('asda')];

        // 返回检测结果
        if ($bool) {
            foreach ($data as $item) {
                if (!$item['status']) {
                    return false;
                }
            }
        }

        return $data;
    }

    /**
     * 下载授权文件
     * @access public
     * @param Model $auth Model授权信息
     * @param string $typeName 完整包、更新包
     * @param integer $version 指定下载版本
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function downloadAuthFile(Model $auth, $typeName = 'complete_file', $version = null)
    {
        // 获取应用跟版本信息
        $app = App::withJoin(['getVersion' => function (Relation $query) {
            $query->withField(['id', 'appid', 'version', 'content', 'update_file', 'complete_file', 'release_time']);
        }])->order('getVersion.id', 'desc');

        // 指定下载版本
        if ($version) {
            $app->where('getVersion.id', $version);
        }

        // 查询应用版本信息
        $app = $app->find($auth->appid);

        if (!$app) {
            return [false, '应用不存在或应用版本未发布'];
        }

        // 应用变量参数
        $appParam = $app->toArray();
        // 应用版本变量参数
        $vParam = $appParam['get_version'];
        unset($appParam['get_version']);
        // 参数列表
        $params = [
            'app'  => $appParam,
            'auth' => $auth->toArray(),
            'v'    => $vParam,
        ];

        // 存储目录
        $dir = Filesystem::getDiskConfig('storage', 'root');
        // 编译目录名
        $md5 = md5('appid_' . $app->id . '_id_' . $vParam['id'] . '_' . $typeName);
        // 完整包文件地址
        $complete_file = $dir . DS . $vParam[$typeName];
        // 编译目录
        $compile_dir = $dir . DS . 'compile' . DS . $md5;
        // zip存放目录
        $zip_dir = $dir . DS . 'zip' . DS . $md5;
        // 压缩包存放路径
        $zip_path = $zip_dir . DS . md5($md5 . microtime(true) . mt_rand(100, 999)) . '.zip';

        if (!file_exists($complete_file)) {
            return [false, '资源文件不存在'];
        }

        // 文件不存在
        if (!is_dir($compile_dir)) {
            // 创建目录
            if (!mkdir($compile_dir, 0777, true)) {
                return [false, '创建编译目录失败'];
            }
            // 解压
            if (!self::extract($complete_file, $compile_dir)) {
                rmdir($compile_dir);
                return [false, '解压失败'];
            }
        }
        // 授权文件添加
        if (notEmpty($app->auth_file_name)) {
            // 授权文件地址
            $auth_file = $compile_dir . DS . $app->auth_file_name;
            // 授权文件编码
            $auth_file_coding = $app->auth_file_coding;
            // 授权文件内容
            $auth_file_content = $app->auth_file_content;

            // 创建授权文件目录
            if (!is_dir(dirname($auth_file))) {
                if (!mkdir(dirname($auth_file), 0777, true)) {
                    return [false, '创建授权文件目录失败'];
                }

            }

            // 变量替换
            foreach ($params as $k => $v) {
                foreach ($v as $name => $value) {
                    $auth_file_content = str_replace('[' . $k . '.' . $name . ']', $value, $auth_file_content);
                }
            }

            // 获取字符串编码
            $mb = mb_detect_encoding($auth_file_content, ['ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5']);
            // 字符串转码
            $auth_file_content = iconv($mb, $auth_file_coding, $auth_file_content);
            // 写入文件
            file_put_contents($auth_file, $auth_file_content);
        }

        // 创建压缩包目录
        if (!is_dir($zip_dir)) {
            if (!mkdir($zip_dir, 0777, true)) {
                return [false, '创建压缩包目录失败'];
            }

        }

        // 创建压缩包
        if (!self::createZip($zip_path, $compile_dir)) {
            return [false, '创建压缩包失败'];
        }

        return [true, $zip_path];
    }

    /**
     * 压缩包提取
     * @access public
     * @param string $file 压缩包地址
     * @param string $outPath 存放目录
     * @return boolean
     */
    public static function extract($file, $outPath)
    {
        $zip = new ZipArchive();
        if ($zip->open($file) === true) {
            $res = $zip->extractTo($outPath);
            $zip->close();
            return $res;
        }
        return false;
    }

    /**
     * 创建压缩包
     * @access public
     * @param string $file 压缩包存放地址
     * @param string $dirPath 压缩目录
     * @return array|int
     */
    public static function createZip($file, $dirPath)
    {
        if (file_exists($file)) {
            unlink($file);
        }

        $zip = new ZipArchive();
        if ($res = $zip->open($file, ZIPARCHIVE::CREATE) === true) {
            $res = self::addFileToZip($dirPath, $zip);
            $zip->close();
            return $res;
        }
        return false;
    }

    /**
     * 将文件添加到压缩包中
     * @access protected
     * @param $path
     * @param ZipArchive $zip
     * @param $sub_dir
     * @return bool|mixed
     */
    protected static function addFileToZip($path, ZipArchive $zip, $sub_dir = '')
    {
        $handler = opendir($path);
        $res     = false;
        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {
                if (is_dir($path . DS . $filename)) {
                    $localPath = $sub_dir . $filename . '/';
                    $res       = self::addFileToZip($path . DS . $filename, $zip, $localPath);
                } else {
                    $res = $zip->addFile($path . DS . $filename, $sub_dir . $filename);
                }
            }
        }
        closedir($handler);
        return $res;
    }

    /**
     * 读入文件并删除
     * @access public
     * @param $file
     * @return string
     */
    public static function readfileAndDel($file)
    {
        $handle  = fopen($file, 'r');
        $content = '';
        while (!feof($handle)) {
            $content .= fread($handle, 8080);
        }
        fclose($handle);
        unlink($file);
        return $content;
    }

    /**
     * 获取QQ登录二维码信息
     * @access public
     * @return array
     */
    public static function getQrcodeUrl()
    {
        return (new QqQrcodeLogin())->getQrcodeUrl();
    }

    /**
     * 获取二维码扫描状态
     * @access public
     * @param String $qrsig
     * @return array
     */
    public static function getQrcodeStatus(String $qrsig)
    {
        return (new QqQrcodeLogin())->queryStatus($qrsig);
    }

    ###################  当前程序通信操作方法   ###################

    /**
     * ######  当前程序服务器通信地址  ######
     * 获取更新服务器URL
     * @access public
     * @return string
     */
    // protected static function getUpdateServerUrl()
    // {
    //     return ['http://auth.fcysat.cn/api/v1'][mt_rand(0, 0)] ?? null;
    // }

    /**
     * 获取授权SDK实例
     * @access protected
     * @return AuthSdk
     */
    // protected static function __getAuthSdk()
    // {
    //     return new AuthSdk([
    //         'number' => config('website.number'),
    //         'auth_id' => config('website.auth_id'),
    //         'auth_key' => config('website.auth_key'),
    //         'api_url' => self::getUpdateServerUrl(),
    //     ]);
    // }

    /**
     * 自身程序授权查询
     * @access public
     * @param array $saveData 授权系统保存的数据
     * @return array
     */
    // public static function selfAuthQuery($saveData = [])
    // {
    //     // SDK实例
    //     $sdk = self::__getAuthSdk();

    //     // 当前域名
    //     $domain = $_SERVER['HTTP_HOST'];

    //     list($status, $data) = $sdk->url($sdk::API_QUERY_AUTH)
    //         ->data('auth_content', self::domain($_SERVER['HTTP_HOST']))
    //         ->data('save_data', $saveData)->request();

    //     // CURL请求错误
    //     if (!$status) {
    //         return [false, '连接授权站失败'];
    //     }

    //     // 验证sign
    //     if (!$sdk->signVerify($data)) {
    //         return [false, '签名验证失败, 请检查授权ID跟授权秘钥'];
    //     }

    //     // 获取加密内容
    //     $data = $sdk->getContent($data);

    //     // 错误信息
    //     if (!$data['status']) {
    //         return [false, $data['info']];
    //     }

    //     return [true, 'success'];
    // }

    /**
     * 获取当前程序更新列表
     * @access public
     * @return array
     */
    // public static function getSelfUpdateList()
    // {
    //     // SDK实例
    //     $sdk = self::__getAuthSdk();

    //     // 版本ID
    //     $number = config('website.number');

    //     if (NULL === $number) {
    //         return [false, '无法获取本地的版本ID'];
    //     }

    //     // 授权系统保存的数据
    //     $saveData = [
    //     ];

    //     list($status, $info) = $sdk->url($sdk::API_QUERY_UPDATE)
    //         // 授权信息
    //         ->data('auth_content', self::domain($_SERVER['HTTP_HOST']))
    //         // 版本ID
    //         ->data('number', $number)
    //         // 授权系统保存的数据
    //         ->data('save_data', $saveData)
    //         ->request();

    //     // 验证sign
    //     if (!$sdk->signVerify($info)) {
    //         return [false, '签名验证失败'];
    //     }

    //     // 获取加密内容
    //     $info = $sdk->getContent($info);

    //     return [$status, $info];
    // }

    /**
     * 下载更新文件
     * @access public
     * @param $id
     * @return array
     */
    // public static function downloadUpdateFile($id)
    // {
    //     // 获取旧版版本ID
    //     $oldVid = config('website.number');
    //     if ((int)$oldVid === (int)$id) {
    //         return [false, '当前版本无需更新'];
    //     }
    //     if ((int)$oldVid > (int)$id) {
    //         return [false, '新版版本ID错误'];
    //     }

    //     // 存储目录
    //     $dir = Filesystem::getDiskConfig('storage', 'root') . DS . 'update' . DS . md5('id_' . $id) . DS;
    //     // 压缩包文件名
    //     $zipFile = $dir . 'sourceCode.zip';
    //     // 压缩包提取存放目录
    //     $zipDir = $dir . 'file' . DS;
    //     // 更新包入口文件
    //     $updateIndex = $zipDir . 'run.php';

    //     // 创建目录
    //     if (!is_dir($dir)) {
    //         mkdir($dir, 0777, true);
    //     }
    //     if (!is_dir($zipDir)) {
    //         mkdir($zipDir, 0777, true);
    //     }

    //     // SDK实例
    //     $sdk = self::__getAuthSdk();

    //     // 授权系统保存的数据
    //     $saveData = [
    //     ];

    //     list($status, $data) = $sdk->url($sdk::API_DOWNLOAD_FILE)
    //         // 版本ID
    //         ->data('number', $id)
    //         ->data('auth_content', self::domain($_SERVER['HTTP_HOST']))
    //         ->data('save_data', $saveData)
    //         ->download($zipFile);

    //     if (!$status) {
    //         return [false, is_array($data) ? $data['info'] : $data];
    //     }

    //     // 提取压缩包文件
    //     $res = self::extract($zipFile, $zipDir);

    //     if (!$res) {
    //         self::rmdir($dir);
    //         return [false, '提取压缩包文件失败'];
    //     }

    //     if (!is_file($updateIndex)) {
    //         self::rmdir($dir);
    //         return [false, '更新包入口文件不存在'];
    //     }

    //     try {
    //         require $updateIndex;

    //         if (!function_exists('updateRun')) {
    //             self::rmdir($dir);
    //             return [false, '更新包核心函数 updateRun 不存在'];
    //         }

    //         // 运行更新
    //         $res = updateRun([$dir, $zipFile, $zipDir, $updateIndex]);

    //         return empty($res) ? [false, '更新包未返回结果'] : $res;
    //     } catch (\Exception $e) {
    //     } catch (\ParseError $e) {
    //     }

    //     $msg = '运行更新包时出现错误';

    //     if (Env::get('APP_DEBUG', false)) {
    //         $msg .= ', Error: ' . $e->getMessage() . '<br>';
    //         $msg .= $e->getFile() . ':' . $e->getLine();
    //     }
    //     return [false, $msg];

    // }

    /**
     * 删除文件夹
     * @access public
     * @param $dir
     * @return bool
     */
    // public static function rmdir($dir)
    // {
    //     if (!is_dir($dir))
    //         return false;
    //     $handle = opendir($dir);
    //     while (($file = readdir($handle)) !== false) {
    //         if ($file != '.' && $file != '..')
    //             is_dir($dir . '/' . $file) ? self::rmdir($dir . '/' . $file) : @unlink($dir . '/' . $file);
    //     }
    //     rmdir($dir . '/' . $file);
    //     if (readdir($handle) == false) {
    //         closedir($handle);
    //     }
    // }

    /**
     * 移动文件夹
     * @access public
     * @param $source
     * @param $target
     * @return bool
     */
    // public static function moveFolder($source, $target)
    // {
    //     if (!is_dir($source)) return false;

    //     $handle = opendir($source);
    //     while (($file = readdir($handle)) !== false) {
    //         if ($file != '.' && $file != '..') {
    //             // 旧文件、目录
    //             $oldPath = $source . '/' . $file;
    //             // 新文件、目录
    //             $newPath = $target . '/' . $file;

    //             if (is_dir($oldPath)) {
    //                 !is_dir($newPath) && mkdir($newPath, 0777, true);
    //                 self::moveFolder($oldPath, $newPath);
    //             } else {
    //                 rename($oldPath, $newPath);
    //             }
    //         }
    //     }
    //     if (readdir($handle) == false) {
    //         closedir($handle);
    //     }
    // }

    /**
     * 获取顶级域名
     * @param string $domain
     * @return string|null
     */
    public static function domain($domain)
    {
        // 域名分割
        $data = explode('.', $domain);
        // 获取分割数量
        $count = count($data);
        // 传入域名错误
        if ($count < 2) {
            return null;
        }
        //判断是否是双后缀
        $doubleSuffix = true;
        // 双后缀域名列表
        $suffix = ['br.com', 'centralnic.net', 'cn.com', 'centralnic.net', 'de.com', 'centralnic.net', 'eu.com', 'centralnic.net', 'gb.com', 'centralnic.net', 'gb.net', 'centralnic.net', 'gr.com', 'centralnic.net', 'hu.com', 'centralnic.net', 'in.net', 'centralnic.net', 'no.com', 'centralnic.net', 'qc.com', 'centralnic.net', 'ru.com', 'centralnic.net', 'sa.com', 'centralnic.net', 'se.com', 'centralnic.net', 'se.net', 'centralnic.net', 'uk.com', 'centralnic.net', 'uk.net', 'centralnic.net', 'us.com', 'centralnic.net', 'uy.com', 'centralnic.net', 'za.com', 'centralnic.net', 'jpn.com', 'centralnic.net', 'web.com', 'centralnic.net', 'verisign-grs.com', 'za.net', 'za.net', 'verisign-grs.com', 'eu.org', 'eu.org', 'za.org', 'za.org', 'pir.org', 'educause.edu', 'dotgov.gov', 'iana.org', 'e164.arpa', 'ripe.net', 'in-addr.arpa', 'ip6.arpa', 'iana.org', 'nic.asia', 'nic.biz', 'nic.cat', 'nic.coop', 'afilias.net', 'nic.jobs', 'afilias.net', 'nic.museum', 'nic.name', 'dotpostregistry.net', 'afilias.net', 'nic.tel', 'nic.travel', 'nic.xxx', 'nic.ac', 'nic.ad', 'aeda.net', 'nic.af', 'nic.ag', 'nic.ai', 'akep.al', 'amnic.net', 'dns.ao', 'nic.ar', 'nic.as', 'priv.at', 'nic.priv', 'nic.at', 'auda.org', 'nic.aw', 'nic.az', 'nic.ba', 'telecoms.gov', 'registry.com', 'dns.be', 'arce.bf', 'onatel.bf', 'register.bg', 'inet.com', 'nic.bi', 'nic.bj', 'afilias-srs.net', 'bnnic.bn', 'nic.bo', 'registro.br', 'register.bs', 'nic.bt', 'norid.no', 'cctld.by', 'nic.net', 'co.ca', 'co.ca', 'cira.ca', 'verisign-grs.com', 'nic.cd', 'dot.cf', 'nic.ch', 'nic.ci', 'nic.cl', 'netcom.cm', 'edu.cn', 'edu.cn', 'cnnic.cn', 'uk.co', 'uk.co', 'nic.co', 'nic.cr', 'nic.cu', 'dns.cv', 'uoc.cw', 'nic.cx', 'nic.cy', 'nic.cz', 'denic.de', 'dk-hostmaster.dk', 'nic.dm', 'nic.do', 'nic.dz', 'nic.ec', 'tld.ee', 'egregistry.eg', 'afridns.org', 'nic.es', 'ethionet.et', 'usp.ac', 'fidc.co', 'nic.fm', 'nic.fo', 'nic.fr', 'dot.ga', 'my.ga', 'nic.gd', 'nic.ge', 'mediaserv.net', 'nic.gh', 'afilias-grs.net', 'nic.gl', 'nic.gm', 'psg.com', 'nic.gp', 'dominio.gq', 'ics.forth', 'nic.gs', 'gov.gu', 'registry.gy', 'hkirc.hk', 'registry.hm', 'nic.hn', 'dns.hr', 'nic.ht', 'nic.hu', 'iedr.ie', 'isoc.org', 'nic.im', 'registry.in', 'nic.io', 'cmc.iq', 'cmc.iq', 'nic.ir', 'isnic.is', 'nic.it', 'mona.uwi', 'dns.jo', 'jprs.jp', 'kenic.or', 'trc.gov', 'nic.ki', 'domaine.km', 'nic.kn', 'star.co', 'nic.kw', 'kyregistry.ky', 'nic.kz', 'nic.la', 'aub.edu', 'afilias-grs.net', 'nic.li', 'nic.lk', 'psg.com', 'nic.ls', 'domreg.lt', 'dns.lu', 'nic.lv', 'nic.ly', 'registre.ma', 'nic.mc', 'nic.md', 'nic.me', 'nic.mg', 'nic.net', 'marnet.mk', 'dot.ml', 'point.ml', 'nic.mm', 'nic.mn', 'monic.mo', 'monic.mo', 'mediaserv.net', 'nic.mr', 'nic.ms', 'nic.org', 'nic.org', 'nic.mu', 'dhiraagu.com', 'nic.mw', 'mynic.my', 'nic.mz', 'na-nic.com', 'intnet.ne', 'nic.nf', 'nic.net', 'nic.ni', 'domain-registry.nl', 'norid.no', 'mos.com', 'cenpac.net', 'iis.nu', 'srs.net', 'registry.om', 'nic.pa', 'yachay.pe', 'registry.pf', 'unitech.ac', 'edu.ph', 'ph.net', 'gov.ph', 'gov.ph', 'dot.ph', 'pknic.net', 'co.pl', 'co.pl', 'dns.pl', 'nic.pm', 'pitcairn.pn', 'afilias-srs.net', 'pnina.ps', 'dns.pt', 'nic.pw', 'nic.py', 'registry.qa', 'nic.re', 'rotld.ro', 'rnids.rs', 'edu.ru', 'informika.ru', 'tcinet.ru', 'ricta.org', 'ricta.org', 'nic.net', 'nic.net', 'afilias-grs.net', 'nic.sc', 'domains.sd', 'iis.se', 'sgnic.sg', 'nic.sh', 'register.si', 'norid.no', 'sk-nic.sk', 'nic.sl', 'nic.sm', 'nic.sn', 'nic.so', 'register.sr', 'nic.ss', 'nic.st', 'tcinet.ru', 'svnet.org', 'tld.sy', 'sispa.org', 'nic.tc', 'nic.td', 'nic.tf', 'nic.tg', 'thnic.co', 'nic.tj', 'dot.tk', 'nic.tl', 'nic.tm', 'ati.tn', 'tonic.to', 'nic.tr', 'nic.tt', 'verisign-grs.com', 'twnic.net', 'tznic.or', 'biz.ua', 'biz.ua', 'co.ua', 'co.ua', 'pp.ua', 'pp.ua', 'co.ug', 'ac.uk', 'ja.net', 'bl.uk', 'british-library.uk', 'gov.uk', 'ja.net', 'icnet.uk', 'jet.uk', 'mod.uk', 'nhs.uk', 'nls.uk', 'parliament.uk', 'police.uk', 'nic.uk', 'fed.us', 'nic.gov', 'nic.us', 'com.uy', 'com.uy', 'nic.org', 'cctld.uz', 'afilias-grs.net', 'nic.ve', 'nic.vg', 'nic.vi', 'vnnic.vn', 'nic.wf', 'website.ws', 'y.net', 'nic.yt', 'ac.za', 'ac.za', 'alt.za', 'alt.za', 'co.za', 'registry.net', 'gov.za', 'gov.za', 'net.za', 'registry.net', 'org.za', 'registry.net', 'web.za', 'registry.net', 'zadna.org', 'zicta.zm', 'zispa.co', 'registry.in', 'registry.in', 'registry.in', 'registry.in', 'nic.kz', 'rnids.rs', 'imena.bg', 'cctld.by', 'sgnic.sg', 'marnet.mk', 'cnnic.cn', 'cnnic.cn', 'registry.in', 'nic.lk', 'registry.in', 'registry.in', 'registry.in', 'registry.in', 'dotukr.com', 'hkirc.hk', 'twnic.net', 'twnic.net', 'nic.dz', 'registry.om', 'nic.ir', 'aeda.net', 'nic.mr', 'registry.in', 'registry.in', 'nic.net', 'registry.in', 'cmc.iq', 'mynic.my', 'monic.mo', 'itdc.ge', 'thnic.co', 'tld.sy', 'tcinet.ru', 'ics.forth', 'registry.in', 'registry.in', 'dotmasr.eg', 'registry.qa', 'nic.lk', 'registry.in', 'amnic.net', 'sgnic.sg', 'pnina.ps'];

        // 遍历对比
        foreach ($suffix as $val) {
            if (strpos($domain, $val)) {
                $doubleSuffix = false;
            }
        }

        if ($doubleSuffix == true) {
            $res = $data[$count - 2] . '.' . $data[$count - 1];
        } else {
            $res = $data[$count - 3] . '.' . $data[$count - 2] . '.' . $data[$count - 1];
        }
        return $res;
    }
}

/**
 * Class Sdk
 */
// class AuthSdk
// {
//     // 查询授权
//     const API_QUERY_AUTH = '/query/auth';
//     // 查询更新
//     const API_QUERY_UPDATE = '/query/update';
//     // 下载文件
//     const API_DOWNLOAD_FILE = '/download/file';

//     /**
//      * 授权信息
//      * @var array
//      */
//     protected $config = [
//         'auth_id' => null,
//         'auth_key' => null,
//         'api_url' => null,
//         'api_encrypt' => false,
//     ];

//     /**
//      * 请求的URL
//      * @var string
//      */
//     protected $url;
//     /**
//      * 请求提交的数据
//      * @var array
//      */
//     protected $data;

//     /**
//      * sdk constructor.
//      * @param array $config
//      */
//     public function __construct(Array $config = [])
//     {
//         $this->config = array_merge($this->config, $config);
//     }

//     /**
//      * 设置URL
//      * @access public
//      * @param $value
//      * @return $this
//      */
//     public function url($value)
//     {
//         $this->url = $this->config['api_url'] . $value;
//         return $this;
//     }

//     /**
//      * 设置提交数据
//      * @access public
//      * @param string|array $name
//      * @param mixed $value
//      * @return $this
//      */
//     public function data($name, $value = null)
//     {
//         if (is_null($value)) {
//             $this->data = array_merge($this->data, $name);
//         } else {
//             $this->data[$name] = $value;
//         }

//         return $this;
//     }

//     /**
//      * 获取请求参数
//      * @access protected
//      * @return array
//      */
//     protected function getParams()
//     {
//         // 请求地址
//         $url = $this->url;
//         // 提交数据
//         $params = [
//             'auth_id' => $this->config['auth_id'],
//             'timestamp' => time(),
//             'data' => json_encode($this->data),
//         ];

//         // 数据加密
//         if ($this->config['api_encrypt']) {
//             $params['data'] = $this->encrypt($params['data']);
//         } else {
//             $params['encrypt'] = 0;
//         }

//         // 计算Sign
//         $sign = $this->getSign($params);
//         $params['sign'] = $sign;

//         // 清空请求参数
//         $this->emptyRequestParams();

//         return [$url, $params];
//     }

//     /**
//      * 发送普通请求
//      * @access public
//      * @return array
//      */
//     public function request()
//     {
//         // 获取请求参数
//         list($url, $params) = $this->getParams();
//         // 发送CURL请求
//         $res = $this->getCurl($url, $params, $header);

//         return [$res[0], $res[0] ? json_decode($res[1], true) : $res[1]];
//     }

//     /**
//      * 发送文件下载请求
//      * @access public
//      * @param $file
//      * @return array
//      */
//     public function download($file)
//     {
//         // 获取请求参数
//         list($url, $params) = $this->getParams();
//         // 发送CURL请求
//         $res = $this->getCurl($url, $params, $header);
//         // 请求错误
//         if (!$res[0]) {
//             return $res;
//         }

//         // 状态码为0
//         if (!$this->getReruenStatus($header)) {
//             return [false, json_decode($res[1], true)];
//         }

//         // 文件保存目录
//         $dir = dirname($file);

//         // 创建目录
//         if (!is_dir($dir)) {
//             mkdir($dir, 0777, true);
//         }

//         // 写入文件
//         $save = @file_put_contents($file, $res[1]);

//         return [$save, $save ? '成功' : '写入文件失败'];
//     }

//     /**
//      * 获取返回的内容
//      * @access public
//      * @param array $res
//      * @return array
//      */
//     public function getContent($res)
//     {
//         // 解密
//         if ($this->config['api_encrypt'] === true && !isset($res['encrypt'])) {
//             $res['data'] = $this->decrypt($res['data']);
//         }
//         // 解析JSON
//         $res['data'] = json_decode($res['data'], true);

//         return $res;
//     }

//     /**
//      * 获取heade返回的状态
//      * @access protected
//      * @param $header
//      * @return integer
//      */
//     protected function getReruenStatus($header)
//     {
//         if (preg_match('/Return-Status:.*?([0-9])/', $header, $status)) {
//             return (int)trim($status[1]);
//         }
//         return 1;
//     }

//     /**
//      * sign验证
//      * @access public
//      * @param array $data
//      * @return bool
//      */
//     public function signVerify($data)
//     {
//         if (!isset($data['sign'])) {
//             return false;
//         }

//         return $this->getSign($data) === $data['sign'];
//     }

//     /**
//      * 清空请求参数
//      * @access protected
//      * @return void
//      */
//     protected function emptyRequestParams()
//     {
//         $this->url = null;
//         $this->data = [];
//     }

//     /**
//      * 计算SIGN
//      * @access protected
//      * @param $params
//      * @return string
//      */
//     protected function getSign($params)
//     {
//         $signPars = "";
//         ksort($params);
//         foreach ($params as $k => $v) {
//             if ("sign" !== $k && "" !== trim((string)$v) && $v !== null) {
//                 $signPars .= $k . "=" . $v . "&";
//             }
//         }
//         $signPars .= "key=" . $this->config['auth_key'];

//         $sign = md5($signPars);
//         return $sign;
//     }

//     /**
//      * 发送CURL请求
//      * return json :
//      *      status: 状态码 1: success; 0: error
//      *      info: 信息
//      *      data: 返回数据
//      *      encrypt: 存在时代表数据未加密
//      *
//      * @access public
//      * @param string $url
//      * @param array $post
//      * @return array
//      */
//     protected function getCurl($url, $post = null, &$header = null)
//     {
//         $ch = curl_init();
//         curl_setopt($ch, CURLOPT_URL, $url);
//         curl_setopt($ch, CURLOPT_TIMEOUT, 10);
//         curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
//         if ($post) {
//             curl_setopt($ch, CURLOPT_POST, 1);
//             curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
//         }
//         curl_setopt($ch, CURLOPT_USERAGENT, 'Chrome 42.0.2311.135');
//         curl_setopt($ch, CURLOPT_ENCODING, "gzip");
//         curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
//         // 返回response头部信息
//         curl_setopt($ch, CURLOPT_HEADER, 1);
//         $ret = curl_exec($ch);
//         $err = curl_error($ch);

//         $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
//         $header = substr($ret, 0, $headerSize);
//         $ret = substr($ret, $headerSize);

//         curl_close($ch);

//         if ($err) {
//             return [false, $err];
//         } else {
//             return [true, $ret];
//         }
//     }

//     /**
//      * 字符串加密
//      * @access protected
//      * @param string $str 字符串明文
//      * @return string
//      */
//     protected function encrypt($str)
//     {
//         $encrypt_key = md5((string)mt_rand(0, 32000));
//         $ctr = 0;
//         $tmp = '';
//         for ($i = 0; $i < strlen($str); $i++) {
//             $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
//             $tmp .= $encrypt_key[$ctr] . ($str[$i] ^ $encrypt_key[$ctr++]);
//         }
//         return base64_encode($this->passport_key($tmp, $this->config['auth_key']));
//     }

//     /**
//      * 字符串解密
//      * @access protected
//      * @param string $str 字符串密文
//      * @return string
//      */
//     protected function decrypt($str)
//     {
//         $str = $this->passport_key(base64_decode($str), $this->config['auth_key']);
//         $tmp = '';
//         for ($i = 0; $i < strlen($str); $i++) {
//             $md5 = $str[$i];
//             if (isset($str[++$i])) {
//                 $tmp .= $str[$i] ^ $md5;
//             }
//         }
//         return $tmp;
//     }

//     protected function passport_key($str, $encrypt_key)
//     {
//         $encrypt_key = md5($encrypt_key);
//         $ctr = 0;
//         $tmp = '';
//         for ($i = 0; $i < strlen($str); $i++) {
//             $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
//             $tmp .= $str[$i] ^ $encrypt_key[$ctr++];
//         }
//         return $tmp;
//     }
// }
