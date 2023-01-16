<?php
declare (strict_types=1);

namespace app\user\controller;

/**
 * Class App
 * @package app\user\controller
 */
class App extends BaseController
{
    /**
     * 应用列表
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function index()
    {
        $this->title('应用列表');
        return view();
    }
}
