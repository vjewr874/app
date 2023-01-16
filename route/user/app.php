<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/10/16 16:08:21
 * 
 */

use think\facade\Route;

Route::group(function () {
    Route::any('/', 'index/index')->name('user');
    Route::any('index', 'index/index')->name('user_index');
    Route::any('login', 'index/login')->name('user_login');
    Route::any('captcha', 'index/captcha')->name('user_captcha');

    /**
     * App Route
     */
    Route::group('app', function () {
        Route::any('index', 'app/index')->name('user_app_index');
    });

    /**
     * Auth Route
     */
    Route::group('auth', function () {
        Route::any('index', 'auth/index')->name('user_auth_index');
    });

    /**
     * Card Route
     */
    Route::group('card', function () {
        Route::any('index', 'card/index')->name('user_card_index');
    });

    /**
     * Card Route
     */
    Route::group('user', function () {
        Route::any('index', 'user/index')->name('user_user_index');
        Route::any('info', 'user/info')->name('user_user_info');
        Route::any('logout', 'user/logout')->name('user_user_logout');
    });
});

/**
 * Ajax Route
 */
Route::group('ajax', function () {
    Route::any('login', 'ajax/login')->name('user_ajax_login');
    Route::any('third/:type', 'ajax/third')->name('user_ajax_third');
    Route::any('getConsoleInfo', 'ajax/getConsoleInfo')->name('user_ajax_get_console_info');
    Route::any('getApp', 'ajax/getApp')->name('user_ajax_get_app');
    Route::any('getAuthList', 'ajax/getAuthList')->name('user_ajax_get_auth_list');
    Route::any('editAuth', 'ajax/editAuth')->name('user_ajax_edit_auth');
    Route::any('editAuthStatus', 'ajax/editAuthStatus')->name('user_ajax_edit_auth_status');
    Route::any('deleteAuth', 'ajax/deleteAuth')->name('user_ajax_delete_auth');
    Route::any('createAuth', 'ajax/createAuth')->name('user_ajax_create_auth');
    Route::any('getCardList', 'ajax/getCardList')->name('user_ajax_get_card_list');
    Route::any('createCard', 'ajax/createCard')->name('user_ajax_create_card');
    Route::any('editCard', 'ajax/editCard')->name('user_ajax_edit_card');
    Route::any('exportCard', 'ajax/exportCard')->name('user_ajax_export_card');
    Route::any('deleteCard', 'ajax/deleteCard')->name('user_ajax_delete_card');
    Route::any('getUserList', 'ajax/getUserList')->name('user_ajax_get_user_list');
    Route::any('createUser', 'ajax/createUser')->name('user_ajax_create_user');
    Route::any('editUser', 'ajax/editUser')->name('user_ajax_edit_user');
    Route::any('editUserStatus', 'ajax/editUserStatus')->name('user_ajax_edit_user_status');
    Route::any('deleteUser', 'ajax/deleteUser')->name('user_ajax_delete_user');
    Route::any('downloadAuthFile', 'ajax/downloadAuthFile')->name('user_ajax_download_auth_file');
    Route::any('getUserInfo', 'ajax/getUserInfo')->name('user_ajax_get_user_info');
    Route::any('editUserInfo', 'ajax/editUserInfo')->name('user_ajax_edit_user_info');
    Route::any('getQrcodeUrl', 'ajax/getQrcodeUrl')->name('user_ajax_get_qrcode_url');
    Route::any('getQrcodeStatus', 'ajax/getQrcodeStatus')->name('user_ajax_get_qrcode_status');
});