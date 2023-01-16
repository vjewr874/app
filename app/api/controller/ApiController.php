<?php

declare (strict_types=1);

namespace app\api\controller;

use app\common\controller\JsonCore;
use app\common\model\Auth;
use app\common\model\AuthLog;
use app\common\model\Config;
use app\UserError;
use think\facade\Request;

class ApiController
{
    // 加密
    const TYPE_ENCRYPT = 0;
    // 解密
    const TYPE_DECRYPT = 1;

    /**
     * 网站配置
     * @var array
     */
    protected $config = [
        // 密文通信
        'api_encrypt' => true,
        // 自动识别加密
        'api_auto_encrypt' => false,
        // 记录查询日志
        'api_log' => true,
    ];

    /**
     * 密钥
     * @var null|string
     */
    protected $key;
    /**
     * 操作类型
     * @var null|string
     */
    protected $type;
    /**
     * 日志参数
     * @var array
     */
    protected $log;

    /**
     * 初始化
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function __construct()
    {
        /**
         * 获取所有配置
         */
        $this->config = array_merge($this->config, Config::getAll());
    }

    /**
     * 获取授权ID秘钥
     * @access protected
     * @param integer $authId 授权ID
     * @return string|boolean
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getAuthKey($authId)
    {
        // 获取授权信息
        $auth = Auth::where('auth_id', $authId)->find();

        return $auth ? $auth->auth_key : false;
    }

    /**
     * 身份验证
     * @access protected
     * @param array $data
     * @return array
     */
    protected function authVerify(Array $data)
    {
        if (empty($data['sign'])) {
            return [false, 'sign参数不能为空'];
        }

        if (empty($data['timestamp'])) {
            return [false, 'timestamp参数不能为空'];
        }

        // 计算时差
        $timestamp = time() - (int)$data['timestamp'];

        // 计算时间戳前后不能相差2分钟
        if (!($timestamp >= -60 && $timestamp <= 60)) {
            return [false, 'timestamp前后相差不能超过120秒'];
        }

        if (is_array($data['data'])) {
            $data['data'] = json_encode($data['data']);
        }

        // 获取SIGN
        $sign = $this->getSign($data);

        return $sign === $data['sign'] ? [true, 'Sign签名验证通过'] : [false, 'Sign签名验证失败'];
    }

    /**
     * 设置密钥
     * @access protected
     * @param string $key 密钥
     * @return $this
     */
    protected function setKey($key = null)
    {
        $this->key = $key;
        return $this;
    }

    /**
     * 设置类型
     * @access protected
     * @param string $type 密钥
     * @return $this
     */
    protected function setType($type = null)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * 获取操作内容
     * @access protected
     * @param null $content
     * @return string|null
     */
    protected function content($content = null)
    {
        switch ($this->type) {

            // 加密
            case static::TYPE_ENCRYPT:
                // 未开启密文通信
                if (!$this->config['api_encrypt']) {
                    return $content;
                }

                return $this->encrypt($content, $this->key);

                break;

            // 解密
            case static::TYPE_DECRYPT:

                // 已开启密文通信
                if ($this->config['api_encrypt']) {
                    $content = $this->decrypt($content, $this->key);
                }

                return json_decode($content, true);

                break;
        }
    }

    /**
     * 返回数据
     * @access protected
     * @param JsonCore $json
     * @return mixed
     */
    protected function result(JsonCore $json)
    {
        // 获取数组列表
        $data = $json->getArray();
        // 将数据转到JSON
        $data['data'] = json_encode($data['data']);
        // 数据加密
        if (!isset($data['encrypt']) && $this->config['api_encrypt']) {
            $data['data'] = $this->encrypt($data['data'], $this->key);
        }
        // 计算sign
        $data['sign'] = $this->getSign($data);

        // 写日志
        $this->writeAuthLog();

        return $json->sign($data['sign'])->data($data['data'])->res()->header([
            'Return-Status' => $data['status'] ?? 0
        ]);
    }

    /**
     * 查询授权信息
     * @access protected
     * @param $authId
     * @param $authContent
     * @param string $field
     * @return array|\think\Model|null
     * @throws UserError
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getAuthFind($authId, $authContent, $field = 'id,appid,qq,auth_content,expire_time,status')
    {
        // 查询授权信息
        $res = Auth::field($field)->where('auth_id', $authId)->find();

        if ($res) {
            $this->log['appid'] = $res->appid;
        }

        // 未查询到授权信息
        if (!$res) {
            ue('授权信息不存在，有疑问联系奇偶猫客服');
        } elseif (!$res['status']) {
            $this->log['type'] = 3;
            ue('当前授权被封禁，有疑问联系奇偶猫客服');
        } elseif ($res['expire_time'] !== 0 && $res['expire_time'] < time()) {
            $this->log['type'] = 2;
            ue('当前授权已到期, 到期时间：' . date('Y-m-d H:i:s', $res['expire_time']));
        } elseif ((string)$res['auth_content'] !== (string)$authContent) {
            $this->log['type'] = 0;
            ue('授权关键不匹配, 当前绑定的授权关键：' . $res['auth_content']);
        }

        $this->log['type'] = 1;

        return $res;
    }

    /**
     * 写授权查询日志
     * @access protected
     * @return void
     */
    protected function writeAuthLog()
    {
        // 当前URL
        $url = Request::controller(true) . '/' . Request::action(true);

        // 记录授权查询日志
        if (in_array($url, ['v1.query/auth', 'v1.query/update', 'v1.download/file']) && $this->config['api_log']) {
            AuthLog::write([
                'type' => $this->log['type'] ?? 0,
                'name' => $this->log['name'] ?? '',
                'appid' => $this->log['appid'] ?? 0,
                'auth_id' => $this->log['body']['auth_id'] ?? 0,
                'auth_content' => $this->log['auth_content'] ?? null,
                'content' => is_string($this->log['body']['data']) ? $this->log['body']['data'] : json_encode($this->log['body']['data']),
            ]);
        }

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
        $signPars .= "key=" . $this->key;

        $sign = md5($signPars);
        return $sign;
    }

    /**
     * 字符串加密
     * @access protected
     * @param string $str 字符串明文
     * @param string $key 密钥
     * @return string
     */
    protected function encrypt($str, $key)
    {
        $encrypt_key = md5((string)mt_rand(0, 32000));
        $ctr = 0;
        $tmp = '';
        for ($i = 0; $i < strlen($str); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $encrypt_key[$ctr] . ($str[$i] ^ $encrypt_key[$ctr++]);
        }
        return base64_encode($this->passport_key($tmp, $key));
    }

    /**
     * 字符串解密
     * @access protected
     * @param string $str 字符串密文
     * @param string $key 密钥
     * @return string
     */
    protected function decrypt($str, $key)
    {
        $str = $this->passport_key(base64_decode($str), $key);
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