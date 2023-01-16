<?php
declare (strict_types=1);

namespace app\common\model;

use think\Model;
use think\model\concern\SoftDelete;
use think\model\Relation;

/**
 * Class App
 * @package app\admin\model
 */
class App extends Model
{
    use SoftDelete;

    protected $name = 'app';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = NULL;

    /**
     * 获取应用列表
     * @access public
     * @param $where array 条件
     * @param $orderBy array|string 排序
     * @param $number integer 条数
     * @return \think\Paginator
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
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

        $data = self::where($where)->order($orderBy[0], $orderBy[1] ?? 'desc')->paginate($number);

        foreach ($data ?: [] as $key => &$value) {
            $value->get_version = Version::field('version,release_time,content')->where('appid', $value->id)->order('id', 'desc')->find();
        }

        return $data;
    }

    /**
     * 应用软删除
     * @access public
     * @param array $id
     * @return bool
     */
    public static function del(Array $id)
    {
        return self::destroy($id);
    }

    public function getVersion()
    {
        return $this->hasOne(Version::class, 'appid', 'id');
    }
}
