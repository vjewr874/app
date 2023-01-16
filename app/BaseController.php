<?php
declare (strict_types=1);

namespace app;

use think\App;
use think\exception\ValidateException;
use think\Validate;
use think\facade\View;
use think\facade\Request;
use app\common\model\Config;

/**
 * 控制器基础类
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 是否批量验证
     * @var bool
     */
    protected $batchValidate = false;

    /**
     * 控制器中间件
     * @var array
     */
    protected $middleware = [];

    /**
     * 构造方法
     * @access public
     * @param App $app 应用对象
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        // 设置模板替换字符串
        $this->__setTplReplaceString();
        // 控制器初始化
        $this->initialize();
    }

    // 初始化
    protected function initialize()
    {
    }

    /**
     * 验证数据
     * @access protected
     * @param array $data 数据
     * @param string|array $validate 验证器名或者验证规则数组
     * @param array $message 提示信息
     * @param bool $batch 是否批量验证
     * @return array|string|true
     * @throws ValidateException
     */
    protected function validate(array $data, $validate, array $message = [], bool $batch = false)
    {
        if (is_array($validate)) {
            $v = new Validate();
            $v->rule($validate);
        } else {
            if (strpos($validate, '.')) {
                // 支持场景
                list($validate, $scene) = explode('.', $validate);
            }
            $class = false !== strpos($validate, '\\') ? $validate : $this->app->parseClass('validate', $validate);
            $v = new $class();
            if (!empty($scene)) {
                $v->scene($scene);
            }
        }

        $v->message($message);

        // 是否批量验证
        if ($batch || $this->batchValidate) {
            $v->batch(true);
        }

        return $v->failException(true)->check($data);
    }

    /**
     * 设置标题
     * @access protected
     * @param $title
     * @param null $name
     * @param null $description
     * @param null $keywords
     * @param null $icon
     * @return void
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    protected function title($title, $name = null, $description = null, $keywords = null, $icon = null)
    {
        // 获取网站设置
        $data = Config::getAll('website_settings');

        // 网站名称
        if (is_null($name)) {
            $name = $data['site_name'] ?? NULL;
        }
        // 网站描述
        if (is_null($description)) {
            $description = $data['site_description'] ?? NULL;
        }
        // 网站关键字
        if (is_null($keywords)) {
            $keywords = $data['site_keywords'] ?? NULL;
        }
        // 网站ICON图标
        if (is_null($icon)) {
            $icon = $data['site_icon'] ?? NULL;
        }

        // 设置模板变量
        View::assign('site', [
                'title' => $title,
                'name' => $name,
                'description' => $description,
                'keywords' => $keywords,
                'icon' => $icon,
                'data' => $data
            ]
        );
    }

    /**
     * 设置模板变量
     * @access protected
     * @param string|array $name
     * @param array $value
     * @return View
     */
    protected function assign($name, $value = [])
    {
        return View::assign($name, $value);
    }

    /**
     * 设置模板替换字符串
     * @access protected
     * @return void
     */
    protected function __setTplReplaceString()
    {
        // 基础替换字符串
        $base = Request::root();
        $root = strpos($base, '.') ? ltrim(dirname($base), DS) : $base;
        if ('' != $root) {
            $root = '/' . ltrim($root, '/');
        }
        $root .= '/..';
        $baseReplace = [
            '__ROOT__' => $root,
            '__STATIC__' => $root . '/static',
            '__CSS__' => $root . '/static/css',
            '__JS__' => $root . '/static/js',
            '__ADMIN__' => $root . '/static/admin',
            '__USER__' => $root . '/static/user',
            '__INDEX__' => $root . '/static/index',
        ];

        View::config(['tpl_replace_string' => $baseReplace]);
    }
}
