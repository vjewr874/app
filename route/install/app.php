<?php

/**
 *  源码来自欧皇源码分享.
 * 
 *  网址：www.ohbbs.cn
 * 
 */

use think\facade\Route;

Route::group(function () {
    Route::any('/', 'index/index')->name('install');
    Route::any('index', 'index/index')->name('install_index');


    /**
     * ajax
     */
    Route::any('getServerInfo', 'index/getServerInfo')->name('install_get_server_info');
    Route::any('installConfigSubmit', 'index/installConfigSubmit')->name('install_config_submit');
    Route::any('startInstall', 'index/startInstall')->name('install_ajax_start_install');
});