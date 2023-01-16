<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/10/16 16:45:55
 * 
 */

namespace app\common\model;


use think\db\exception\DbException;
use think\facade\Db;
use think\Model;

/**
 * Class AuthLog
 * @package app\common\model
 */
class AuthLog extends Model
{
    use newPaginate;

    protected $name = 'auth_log';
    public $autoWriteTimestamp = true;

    /**
     * 获取授权查询日志列表
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
        if ($number > 90) {
            $number = 90;
        }
        // 总条数
        $count = self::getCount($where);

        if (!$count) {
            return [$count, []];
        }

        // 查询结果
        $data = self::newPaginate(
            self::where($where)->order($orderBy[0], $orderBy[1] ?? 'desc'),
            ['list_rows' => $number]
        );

        return [$data->total(), $data->items()];
    }

    /**
     * 获取数据条数
     * @access public
     * @param $where
     * @return int
     */
    public static function getCount($where)
    {
        return self::where($where)->count();
    }

    /**
     * 获取未授权站点数量
     * @access public
     * @return integer
     */
    public static function getNotAuthCount()
    {
        $db = new static();
        $sql = $db->field('id')->where('type', 0)->group('auth_id')->buildSql();
        $sql = $db->field('count(*) number')->table($sql)->alias('a1')->limit(1)->buildSql();
        $number = Db::query($sql);
        return $number[0]['number'];
    }

    /**
     * 写日志
     * @access public
     * @param array $data
     * @return bool
     */
    public static function write($data)
    {
        return (new static())->save([
            'type' => $data['type'] ?? null,
            'name' => $data['name'] ?? null,
            'appid' => $data['appid'] ?? null,
            'auth_id' => $data['auth_id'] ?? null,
            'auth_content' => $data['auth_content'] ?? null,
            'content' => $data['content'] ?? '无',
            'ip' => $data['ip'] ?? request()->ip(),
        ]);
    }
}