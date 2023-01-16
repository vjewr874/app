<?php
// 应用公共文件

use think\exception\HttpResponseException;

if (!function_exists('notEmpty')) {
    /**
     * 不等于空
     * @param $content
     * @return boolean
     */
    function notEmpty($content)
    {
        return !('' === trim($content) && empty($content));
    }
}

if (!function_exists('adminUrl')) {
    /**
     * 获取Admin URL
     * @access public
     * @param $url
     * @param string $type
     * @param array $vars
     * @param bool $suffix
     * @param bool $domain
     * @return false|int
     */
    function adminUrl($url, $type = 'ajax', array $vars = [], $suffix = true, $domain = false)
    {
        // 管理后台路径
        $admin = url('admin');
        // 获取URL地址
        $res = url($url, $vars, $suffix, $domain);

        if ($type == 'view') {
            // 视图路径拼接
            $admin .= '/views';
            // 替换多余的分隔符
            $admin = preg_replace("/\/+/", "/", $admin);
        }
        // 截取字符串
        return substr($res, strlen($admin));
    }
}

if (!function_exists('adminView')) {
    /**
     * 获取Admin Views URL
     * @access public
     * @param $url
     * @param array $vars
     * @return false|int
     */
    function adminView($url, array $vars = [])
    {
        return adminUrl($url, 'view', $vars, false);
    }
}

if (!function_exists('ue')) {
    /**
     * 用户自定义错误
     * @param mixed ...$args
     * @return void
     * @throws \app\UserError
     */
    function ue(...$args)
    {
        throw new \app\UserError(...$args);
    }
}

if (!function_exists('randomString')) {
    /**
     * 随机字符串
     * @param int $length 字符串长度
     * @param null|integer $group 生成多组
     * @param string $split 分隔符
     * @return string
     */
    function randomString($length = 8, $group = null, $split = '-')
    {
        if (!is_null($group) && $group >= 1) {
            $resString = '';

            for ($i = 1; $i <= $group; $i++) {
                $resString .= randomString($length) . $split;
            }

            return trim($resString, $split);
        }
        // 密码字符集，可任意添加你需要的字符
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $string = '';
        for ($i = 0; $i < $length; $i++) {
            $string .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $string;
    }
}

if (!function_exists('params')) {
    /**
     * 参数过滤
     * @param array $field 要取出的参数数组
     * @param array $data 原参数数组
     * @return array
     */
    function params($field, $data)
    {
        $res = [];
        foreach ($field as $value) {
            $res[$value] = $data[$value] ?? null;
        }
        return $res;
    }
}

if (!function_exists('getSessionId')) {
    /**
     * 获取Session ID
     * @return mixed
     */
    function getSessionId()
    {
        $SessionName = config('session.name');
        return cookie($SessionName);
    }
}

if (!function_exists('exitContent')) {
    /**
     * 停止内容
     * @param $data
     * @return void
     */
    function exitContent($data)
    {
        throw new HttpResponseException($data);
    }
}
