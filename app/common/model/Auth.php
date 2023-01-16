<?php

namespace app\common\model;

use think\db\exception\DbException;
use think\Model;
use think\model\concern\SoftDelete;
use think\model\Relation;

/**
 * Class Auth
 * @package app\common\model
 */
class Auth extends Model
{
    use SoftDelete;
    use newPaginate;

    protected $name = 'auth';
    protected $autoWriteTimestamp = true;
    protected $deleteTime = 'delete_time';
    protected $defaultSoftDelete = NULL;
    protected $options = [];

    /**
     * 获取授权列表
     * @access public
     * @param $where
     * @param $orderBy
     * @param $number
     * @param \think\db\Query|null $authModel
     * @return array
     * @throws DbException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getList($where, $orderBy, $number, \think\db\Query $authModel = null)
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

        if (is_null($authModel)) {
            $authModel = new static();
        }

        // 查询结果
        $data = self::newPaginate(
            $authModel->where($where)->with(['getAppInfo' => function (Relation $query) {
                $query->field('id,name')->withLimit(1);
            }])->withAttr('status', function ($value, $data) {
                return $data['expire_time'] != 0 && $data['expire_time'] < time() ? 2 : $value;
            })->order($orderBy[0], $orderBy[1] ?? 'desc'),
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
    public static function getCount($where, \think\db\Query $authModel = null)
    {
        if (is_null($authModel)) {
            $authModel = new static();
        }
        return $authModel->where($where)->count();
    }

    /**
     * 授权软删除
     * @access public
     * @param array $id
     * @return bool
     */
    public static function del(Array $id)
    {
        return self::destroy($id);
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

    /**
     * 新增前事件
     * @access public
     * @param Model $data
     * @return mixed|void
     */
    public static function onBeforeInsert($data)
    {
        // 随机授权ID
        $data->auth_id = time() . mt_rand(1000, 9999);
        // 随机授权秘钥
        $data->auth_key = md5($data->auth_id . mt_rand(1000, 9999));

        if (!is_numeric($data->expire_time)) {
            // 日期转到时间
            $data->expire_time = $data->expire_time ? strtotime($data->expire_time) : 0;
        }
    }

    /**
     * 更新前事件
     * @access public
     * @param Model $data
     * @return mixed|void
     */
    public static function onBeforeUpdate($data)
    {
        // 日期转到时间
        if (isset($data->expire_time) && !is_numeric($data->expire_time)) {
            $data->expire_time = $data->expire_time ? strtotime($data->expire_time) : 0;
        }
    }
}
