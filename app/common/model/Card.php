<?php
declare (strict_types=1);

namespace app\common\model;

use think\db\exception\DbException;
use think\Model;
use think\model\concern\SoftDelete;
use think\model\Relation;

/**
 * Class Card
 * @package app\admin\model
 */
class Card extends Model
{
    use SoftDelete;
    use newPaginate;

    protected $name = 'card_password';
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = NULL;

    /**
     * 获取应用列表
     * @access public
     * @param $where array 条件
     * @param $orderBy array|string 排序
     * @param $number integer 条数
     * @return array
     * @throws DbException
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
            self::where($where)->with(['getAppInfo' => function (Relation $query) {
                $query->field('id,name')->withLimit(1);
            }])->order($orderBy[0], $orderBy[1] ?? 'desc'),
            ['list_rows' => $number]
        );

        return [$data->total(), $data->items()];
    }

    /**
     * 卡密软删除
     * @access public
     * @param array $id
     * @return bool
     */
    public static function del(Array $id)
    {
        return self::destroy($id);
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
     * App模型关联
     * @access public
     * @return \think\model\relation\HasOne
     */
    public function getAppInfo()
    {
        return $this->hasOne(App::class, 'id', 'appid');
    }
}
