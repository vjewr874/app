<?php
declare (strict_types=1);

namespace app\admin\controller;

/**
 * 站点类
 * Class Site
 * @package app\admin\controller
 */
class Site extends BaseController
{
    /**
     * 网站设置
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function setting()
    {
        $this->title('网站设置');
        return view();
    }

    /**
     * 公告列表
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function notice()
    {
        $this->title('公告列表');
        return view();
    }
}
