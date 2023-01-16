<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/11/18 18:01:35
 * 
 */

namespace app\common\model;

use think\db\BaseQuery;
use think\Paginator;

/**
 * Trait newPaginate
 * @package app\common\model
 */
trait newPaginate
{
    public static function newPaginate(BaseQuery $query, $config = [])
    {
        $defaultConfig = [
            'query' => [], //url额外参数
            'fragment' => '', //url锚点
            'var_page' => 'page', //分页变量
            'list_rows' => 15, //每页数量
            'page' => '',// 当前所在页
        ];

        $config = array_merge($defaultConfig, $config);

        // 获取当前查询参数
        $newOption = $options = $query->getOptions();
        // 获取数据总数
        $total = $query->count();

        // 当前分页数
        $page = !empty($config['page']) ? $config['page'] : request()->param($config['var_page'] . '/d');

        // 计算总页数
        $newPage = ceil($total / $config['list_rows']);

        // 过滤页数，防止负页数
        $page = $page < 1 ? 1 : $page;
        // 过滤页数，防止超页数
        $newPage = $page > $newPage ? $newPage : $page;

        // 删除多余的参数
        unset($options['page'], $options['limit'], $options['field']);

        // 设置当前查询参数
        $w = self::option($options);

        // 查询出所有ID
        $results = $w->field('id')->page($newPage, $config['list_rows'])->select();

        // 取出ID列表
        $results = $results ? $results->toArray() : [];
        $id = [];
        foreach ($results as $v) {
            $id[] = $v['id'];
        }

        // 删除多余的参数
        unset($options['where']);
        isset($newOption['field']) && $options['field'] = $newOption['field'];

        // 设置当前查询参数
        $w = self::option($options);
        // 通过ID查询出结果
        $results = $w->where('id', 'in', $id)->select();

        return Paginator::make($results, $config['list_rows'], $newPage, $total, false, $config);
    }

    public static function option(array $options)
    {
        // 模型实例化
        $w = (new static())->newQuery();
        // 设置当前查询参数
        foreach ($options as $key => $value) {
            $w->setOption($key, $value);
        }
        return $w;
    }
}