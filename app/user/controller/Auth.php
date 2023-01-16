<?php
declare (strict_types=1);

namespace app\user\controller;

use think\Request;

class Auth extends BaseController
{
    /**
     * 授权列表
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title('授权列表');
        return view();
    }
}
