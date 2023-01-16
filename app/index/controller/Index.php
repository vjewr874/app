<?php
declare (strict_types=1);

namespace app\index\controller;

use app\BaseController;

class Index extends BaseController
{
    /**
     * 首页
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title('首页');
        return view();
    }
}
