<?php
declare (strict_types=1);

namespace app\admin\controller;

/**
 * 卡密类
 * Class Card
 * @package app\admin\controller
 */
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

    /**
     * 生成卡密
     * @access public
     * @return \think\response\View
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function create()
    {
        $this->title('生成卡密');
        return view();
    }
}
