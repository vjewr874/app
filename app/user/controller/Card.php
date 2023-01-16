<?php
declare (strict_types=1);

namespace app\user\controller;

use think\Request;

class Card extends BaseController
{
    /**
     * 卡密列表
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title('卡密列表');
        return view();
    }
}
