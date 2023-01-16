<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/06/09 9:56:01
 * 
 */

namespace app\common\model;

use think\facade\Cache;
use think\facade\Db;

class Config
{
    protected $name = 'config';
    const undefined = 'undefined';

    /**
     * 缓存驱动
     * @var \think\Cache
     */
    protected static $cache;

    /**
     * 获取所有配置
     * @access public
     * @return array|bool|mixed|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function getAll()
    {
        // 获取缓存
        $data = self::cache('option_config_all');

        // 缓存不存在
        if ($data === self::undefined) {
            // 查询数据库配置
            $res = Db::field('name,value')->name('config')->select();

            if ($res) {
                $res = $res->toArray();
                $data = [];
                foreach ($res as $value) {
                    $data[$value['name']] = $value['value'];
                }
            }
            // 写入缓存
            self::cache('option_config_all', $data);
        }

        return $data;
    }

    /**
     * 删除缓存
     * @access public
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public static function deleteCache()
    {
        // 删除缓存
        self::__cache()->delete('option_config_all');
        // 写入缓存
        self::getAll();
    }

    /**
     * 缓存
     * @access protected
     * @param $name string 键名
     * @param $value mixed 默认值
     * @return bool|mixed
     */
    protected static function cache($name, $value = false)
    {
        // 写入缓存
        if (false !== $value) {
            return self::__cache()->set($name, $value);
        }

        // 获取缓存
        return self::__cache()->get($name, self::undefined);
    }

    /**
     * 缓存驱动
     * @access protected
     * @return \think\Cache
     */
    protected static function __cache()
    {
        // 实例化缓存引擎
        if (is_null(static::$cache)) {
            static::$cache = Cache::store('common');
        }
        return static::$cache;
    }
}
