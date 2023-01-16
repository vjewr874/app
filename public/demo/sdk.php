<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/11/16 16:21:46
 * 
 */

/**
 * Class Sdk
 */
class Sdk
{
    // 查询授权
    const API_QUERY_AUTH = '/query/auth';
    // 查询更新
    const API_QUERY_UPDATE = '/query/update';
    // 下载文件
    const API_DOWNLOAD_FILE = '/download/file';

    /**
     * 授权信息
     * @var array
     */
    protected $config = [
        'auth_id' => null,
        'auth_key' => null,
        'api_url' => 'http://api.zimoyun.cn/v1',
        'api_encrypt' => false,
    ];

    /**
     * 请求的URL
     * @var string
     */
    protected $url;
    /**
     * 请求提交的数据
     * @var array
     */
    protected $data;

    /**
     * sdk constructor.
     * @param array $config
     */
    public function __construct(Array $config = [])
    {
        $this->config = array_merge($this->config, $config);
    }

    /**
     * 设置URL
     * @access public
     * @param $value
     * @return $this
     */
    public function url($value)
    {
        $this->url = $this->config['api_url'] . $value;
        return $this;
    }

    /**
     * 设置提交数据
     * @access public
     * @param string|array $name
     * @param mixed $value
     * @return $this
     */
    public function data($name, $value = null)
    {
        if (is_null($value)) {
            $this->data = array_merge($this->data, $name);
        } else {
            $this->data[$name] = $value;
        }

        return $this;
    }

    /**
     * 获取请求参数
     * @access protected
     * @return array
     */
    protected function getParams()
    {
        // 请求地址
        $url = $this->url;
        // 提交数据
        $params = [
            'auth_id' => $this->config['auth_id'],
            'timestamp' => time(),
            'data' => json_encode($this->data),
        ];

        // 数据加密
        if ($this->config['api_encrypt']) {
            $params['data'] = $this->encrypt($params['data']);
        } else {
            $params['encrypt'] = 0;
        }

        // 计算Sign
        $sign = $this->getSign($params);
        $params['sign'] = $sign;


        // 清空请求参数
        $this->emptyRequestParams();

        return [$url, $params];
    }

    /**
     * 发送普通请求
     * @access public
     * @return array
     */
    public function request()
    {
        // 获取请求参数
        list($url, $params) = $this->getParams();
        // 发送CURL请求
        $res = $this->getCurl($url, $params, $header);

        return [$res[0], $res[0] ? json_decode($res[1], true) : $res[1]];
    }

    /**
     * 发送文件下载请求
     * @access public
     * @param $file
     * @return array
     */
    public function download($file)
    {
        // 获取请求参数
        list($url, $params) = $this->getParams();
        // 发送CURL请求
        $res = $this->getCurl($url, $params, $header);
        // 请求错误
        if (!$res[0]) {
            return $res;
        }

        // 状态码为0
        if (!$this->getReruenStatus($header)) {
            return [false, json_decode($res[1], true)];
        }

        // 文件保存目录
        $dir = dirname($file);

        // 创建目录
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // 写入文件
        $save = @file_put_contents($file, $res[1]);

        return [$save, $save ? '成功' : '写入文件失败'];
    }

    /**
     * 获取返回的内容
     * @access public
     * @param array $res
     * @return array
     */
    public function getContent($res)
    {
        // 解密
        if ($this->config['api_encrypt'] === true && !isset($res['encrypt'])) {
            $res['data'] = $this->decrypt($res['data']);
        }
        // 解析JSON
        $res['data'] = json_decode($res['data'], true);

        return $res;
    }

    /**
     * 获取heade返回的状态
     * @access protected
     * @param $header
     * @return integer
     */
    protected function getReruenStatus($header)
    {
        if (preg_match('/Return-Status:.*?([0-9])/', $header, $status)) {
            return (int)trim($status[1]);
        }
        return 1;
    }

    /**
     * sign验证
     * @access public
     * @param array $data
     * @return bool
     */
    public function signVerify($data)
    {
        if (!isset($data['sign'])) {
            return false;
        }

        return $this->getSign($data) === $data['sign'];
    }

    /**
     * 清空请求参数
     * @access protected
     * @return void
     */
    protected function emptyRequestParams()
    {
        $this->url = null;
        $this->data = [];
    }

    /**
     * 计算SIGN
     * @access protected
     * @param $params
     * @return string
     */
    protected function getSign($params)
    {
        $signPars = "";
        ksort($params);
        foreach ($params as $k => $v) {
            if ("sign" !== $k && "" !== trim((string)$v) && $v !== null) {
                $signPars .= $k . "=" . $v . "&";
            }
        }
        $signPars .= "key=" . $this->config['auth_key'];

        $sign = md5($signPars);
        return $sign;
    }

    /**
     * 发送CURL请求
     * return json :
     *      status: 状态码 1: success; 0: error
     *      info: 信息
     *      data: 返回数据
     *      encrypt: 存在时代表数据未加密
     *
     * @access public
     * @param string $url
     * @param array $post
     * @return array
     */
    protected function getCurl($url, $post = null, &$header = null)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_USERAGENT, 'Chrome 42.0.2311.135');
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // 返回response头部信息
        curl_setopt($ch, CURLOPT_HEADER, 1);
        $ret = curl_exec($ch);
        $err = curl_error($ch);

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($ret, 0, $headerSize);
        $ret = substr($ret, $headerSize);

        curl_close($ch);

        // var_dump($header, $ret);
        // echo PHP_EOL, PHP_EOL, PHP_EOL;

        if ($err) {
            return [false, $err];
        } else {
            return [true, $ret];
        }
    }

    /**
     * 字符串加密
     * @access protected
     * @param string $str 字符串明文
     * @return string
     */
    protected function encrypt($str)
    {
        $encrypt_key = md5((string)mt_rand(0, 32000));
        $ctr = 0;
        $tmp = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $encrypt_key[$ctr] . ($str[$i] ^ $encrypt_key[$ctr++]);
        }
        return base64_encode($this->passport_key($tmp, $this->config['auth_key']));
    }

    /**
     * 字符串解密
     * @access protected
     * @param string $str 字符串密文
     * @return string
     */
    protected function decrypt($str)
    {
        $str = $this->passport_key(base64_decode($str), $this->config['auth_key']);
        $tmp = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $md5 = $str[$i];
            if (isset($str[++$i])) {
                $tmp .= $str[$i] ^ $md5;
            }
        }
        return $tmp;
    }

    protected function passport_key($str, $encrypt_key)
    {
        $encrypt_key = md5($encrypt_key);
        $ctr = 0;
        $tmp = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $str[$i] ^ $encrypt_key[$ctr++];
        }
        return $tmp;
    }
}