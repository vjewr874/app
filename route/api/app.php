<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/11/13 13:17:15
 * 
 */

use think\facade\Route;


/**
 * V1 API Router
 */
Route::group('v1', function () {
    /**
     * Query Route
     */
    Route::group('query', function () {
        Route::any('auth', 'v1.Query/auth')->name('v1_query_auth');
        Route::any('update', 'v1.Query/update')->name('v1_query_update');
    });

    /**
     * Download Route
     */
    Route::group('download', function () {
        Route::any('file', 'v1.Download/file')->name('v1_download_file');
    });
});