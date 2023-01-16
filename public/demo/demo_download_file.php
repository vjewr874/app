<?php

/**
 * 文件下载
 */

require 'common.php';

$sdk = new Sdk($config);

// 当前域名
$domain = $_SERVER['HTTP_HOST'];

// 授权系统保存的数据
$saveData = [
];

list($status, $data) = $sdk->url($sdk::API_DOWNLOAD_FILE)
    // 版本ID
    ->data('number', 7)
    ->data('auth_content', $domain)
    ->data('save_data', $saveData)
    ->download(__DIR__ . '/test.zip');

if (!$status) {
    // $data为array: 错误一般为 授权信息不存在、签名验证失败、应用或版本不存在
    // $data为string: 文件下载失败
    die(is_array($data) ? $data['info'] : $data);
}

var_dump($status, $data);

echo '下载成功';

