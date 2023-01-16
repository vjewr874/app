<?php

/**
 * 授权查询Demo
 */

require 'common.php';

$sdk = new Sdk($config);

// 当前域名
$domain = $_SERVER['HTTP_HOST'];

// 授权系统保存的数据
$saveData = [
    'time' => time(),
    'msg' => 'demo',
    // ... 自定义 ...
];

list($status, $data) = $sdk->url($sdk::API_QUERY_AUTH)
    ->data('auth_content', $domain)
    ->data('save_data', $saveData)->request();

// CURL请求错误
if (false === $status) {
    die($data);
}

// 验证sign
if (!$sdk->signVerify($data)) {
    die('签名验证失败');
}

// 获取加密内容
$data = $sdk->getContent($data);

// 错误信息
if (!$data['status']) {
    die($data['info']);
}

echo '<pre>';

var_dump($data);
echo '授权正常';

