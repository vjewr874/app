<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/10/16 16:45:55
 * 
 */

namespace app\common\model;


use think\Model;
use app\common\model\App as AppModel;
use think\model\concern\SoftDelete;

class Notice extends Model
{
    use newPaginate;
    protected $name = 'notice';

    /**
     * 获取公告列表
     * @access public
     * @param $where array 条件
     * @param $orderBy array|string 排序
     * @param $number integer 条数
     * @return array
     */
    public static function getList($where, $orderBy, $number)
    {
        if (!is_array($orderBy)) {
            $orderBy = [$orderBy];
        }

        if ($number < 10) {
            $number = 10;
        }
        if ($number > 100) {
            $number = 100;
        }

        // 查询结果
        $data = self::newPaginate(
            self::where($where)->order($orderBy[0], $orderBy[1] ?? 'desc'),
            ['list_rows' => $number]
        );

        return [$data->total(), $data->items()];
    }


    /**
     * 公告删除
     * @access public
     * @param array $id
     * @return bool
     * @throws \Exception
     */
    public static function del(Array $id)
    {
        return self::where('id', 'in', $id)->delete();
    }
}