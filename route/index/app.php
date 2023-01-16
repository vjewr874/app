<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/10/21 13:01:01
 * 
 */

use think\facade\Route;

Route::group(function () {
    Route::any('/', 'index/index')->name('index');
    Route::any('index', 'index/index')->name('index_index');
});

/**
 * Ajax Route
 */
Route::group('ajax', function () {
    Route::any('getAppList', 'ajax/getAppList')->name('index_ajax_get_app_list');
    Route::any('queryAuth', 'ajax/queryAuth')->name('index_ajax_query_auth');
    Route::any('createAuth', 'ajax/createAuth')->name('index_ajax_create_auth');
    Route::any('agentQuery', 'ajax/agentQuery')->name('index_ajax_agent_query');
    Route::any('getUpdateHistory', 'ajax/getUpdateHistory')->name('index_ajax_get_update_history');
    Route::any('getQqQrcode', 'ajax/getQqQrcode')->name('index_ajax_get_qq_qrcode');
    Route::any('getQqQrcodeStatus', 'ajax/getQqQrcodeStatus')->name('index_ajax_get_qq_qrcode_status');
    Route::any('downloadSourceCode', 'ajax/downloadSourceCode')->name('index_ajax_download_source_code');
});

