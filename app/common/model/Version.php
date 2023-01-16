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

class Version extends Model
{
    use SoftDelete;

    protected $name = 'version';
    protected $createTime = 'release_time';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = NULL;

    /**
     * 获取版本列表
     * @access public
     * @param $where array 条件
     * @param $orderBy array|string 排序
     * @param $number integer 条数
     * @return \think\Paginator
     * @throws \think\db\exception\DbException
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

        return self::where($where)->order($orderBy[0], $orderBy[1] ?? 'desc')->paginate($number);
    }

    /**
     * 版本软删除
     * @access public
     * @param array $id
     * @return bool
     */
    public static function del(Array $id)
    {
        return self::destroy($id);
    }

    public function getApp()
    {
        return $this->hasOne(AppModel::class, 'id', 'appid');
    }
}