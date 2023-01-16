<?php
declare (strict_types=1);

namespace app\middleware;

use think\Response;

class SiteInit
{
    protected $domainBind;
    protected $app = ['admin', 'user', 'index', 'api'];

    /**
     * 处理请求
     * @access public
     * @param $request
     * @param \Closure $next
     * @return Response
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function handle($request, \Closure $next)
    {
        if (!$this->install() && app('http')->getName() !== 'install') {
            return redirect((string)url('install/index/index'));
        }

        return $next($request);
    }

    /**
     * 安装检测
     * @access protected
     * @return boolean
     */
    protected function install()
    {
        return is_file(config('site.installLock'));
    }
}
