<?php
declare (strict_types=1);

namespace app\api\controller\v1;

use app\api\controller\ApiController;
use app\common\controller\Json;

class BaseController extends ApiController
{
    /**
     * 请求内容
     * @var array
     */
    protected $body = [
        // 授权ID
        'auth_id' => null,
        // 时间戳
        'timestamp' => 0,
        // 内容
        'data' => null,
        // sign
        'sign' => null,
    ];

    /**
     * 初始化
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function __construct()
    {
        // 父类初始化
        parent::__construct();
        // 获取请求参数
        $this->body = array_merge($this->body, request()->param());
        // 获取授权秘钥
        $authKey = $this->getAuthKey($this->body['auth_id']);

        // 自动识别加密
        if ($this->config['api_auto_encrypt']) {
            $this->config['api_encrypt'] = isset($this->body['encrypt']) ? false : true;
        }

        $this->setLogData(['body' => $this->body]);

        // 设置KEY
        $this->setKey($authKey);

        // 授权信息验证
        if (false === $authKey) {
            $this->setLogData(['name' => '授权ID不存在']);
            exitContent($this->result(Json::error('授权ID不存在')->encrypt(0)));
        }

        // 身份验证
        list($status, $info) = $this->authVerify($this->body);
        // 身份验证不通过
        if (false === $status) {
            $this->setLogData(['name' => '身份验证不通过']);
            exitContent($this->result(Json::error($info)->encrypt(0)));
        }

        // 解密数据内容
        $this->body['data'] = $this->setType(static::TYPE_DECRYPT)
            ->content($this->body['data']);
    }

    /**
     * 设置日志
     * @access protected
     * @param $array
     * @return $this
     */
    protected function setLogData($array)
    {
        $this->log = array_merge($this->log ?: [], $array);

        return $this;
    }
}
