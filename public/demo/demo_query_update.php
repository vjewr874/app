<?php

/**
 * 更新查询
 */

require 'common.php';

$sdk = new Sdk($config);

// 当前域名
$domain = $_SERVER['HTTP_HOST'];

// 授权系统保存的数据
$saveData = [
];

list($status, $data) = $sdk->url($sdk::API_QUERY_UPDATE)
    // 授权信息
    ->data('auth_content', $domain)
    // 版本ID
    ->data('number', $config['number'])
    // 授权系统保存的数据
    ->data('save_data', $saveData)
    ->request();

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

// 新版本列表在 $data['data'] 中, 空则为最新版，新版可通过ID下载,，详情参见 demo_download_file.php

var_dump($data);

