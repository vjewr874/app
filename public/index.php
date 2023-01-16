<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2019 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
// 欧皇源码
// www.ohbbs.cn
// 微信公众号：欧皇源码


// [ 应用入口文件 ]
namespace think;

define('DS', DIRECTORY_SEPARATOR);
define('ROOT_PATH', __DIR__ . '/../');

// PHP版本检测
version_compare(PHP_VERSION, '7.1.0', '>') or die('PHP version >= 7.1.0');

require __DIR__ . '/../vendor/autoload.php';

// 执行HTTP应用并响应
$http = (new App())->http;

$response = $http->run();

$response->send();

$http->end($response);
