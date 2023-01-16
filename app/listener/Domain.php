<?php

namespace app\listener;

use app\common\model\Config;

class Domain
{
    protected $domainBind;
    protected $app = ['admin', 'user', 'index', 'api'];

    /**
     * Domain constructor.
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function __construct()
    {
        \think\facade\Config::set(['installLock' => ROOT_PATH . 'app' . DS . 'install' . DS . 'install.lock'], 'site');
        \think\facade\Config::set(['install' => is_file(config('site.installLock'))], 'site');

        if (config('site.install')) {
            \think\facade\Config::set(Config::getAll(), 'config');
        }
    }

    /**
     * 绑定域名
     * @access public
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handle()
    {
        if (config('site.install')) {
            \think\facade\Config::set(['domain_bind' => $this->getDomainBind(), 'deny_app_list' => $this->getDenyApp()], 'app');
        }
    }

    /**
     * 获取域名绑定信息
     * @access protected
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getDomainBind()
    {
        if (!is_null($this->domainBind)) {
            return $this->domainBind;
        }

        // 域名列表
        $domain = [];
        // 获取配置
        $config = config('config');

        foreach ($this->app as $value) {
            // 获取域名信息
            $data = $config[$value . '_domain'] ?? '';
            // 未设置域名信息
            if (empty($data)) {
                continue;
            }
            // 分割域名
            $data = explode(',', $data);
            foreach ($data as $v) {
                $domain[$v] = $value;
            }
        }
        return $this->domainBind = $domain;
    }

    /**
     * 获取拒绝访问APP列表
     * @access protected
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function getDenyApp()
    {
        // 拒绝访问APP列表
        $app = [];
        // 域名列表
        $domain = $this->getDomainBind();
        foreach ($this->app as $value) {
            if (!in_array($value, $app) && in_array($value, $domain)) {
                $app[] = $value;
            }
        }

        $app[] = 'install';

        return $app;
    }
}