<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/10/14 14:28:26
 * 
 */

use think\facade\Route;

Route::group(function () {
    Route::any('/', 'index/index')->name('admin');
    Route::any('index', 'index/index')->name('admin_index');
    Route::any('login', 'index/login')->name('admin_login');
    Route::any('captcha', 'index/captcha')->name('admin_captcha');
    Route::any('logout', 'index/logout')->name('admin_logout');
});

/**
 * Views Route
 */
Route::group('views', function () {
    Route::any('layout', 'index/layout')->name('admin_layout');
    Route::any('template/:type/:name', 'index/template')->name('admin_template');
    Route::any('home', 'index/home')->name('admin_index_home');
    Route::any('edit', 'index/edit')->name('admin_index_edit');

    /**
     * Site Route
     */
    Route::group('site', function () {
        Route::any('setting', 'site/setting')->name('admin_site_setting');
        Route::any('notice', 'site/notice')->name('admin_site_notice');
    });

    /**
     * UserName Route
     */
    Route::group('user', function () {
        Route::any('index', 'user/index')->name('admin_user_index');
        Route::any('create', 'user/create')->name('admin_user_create');
        Route::any('edit', 'user/edit')->name('admin_user_edit');
        Route::any('auth', 'user/auth')->name('admin_user_auth');
    });

    /**
     * App Route
     */
    Route::group('app', function () {
        Route::any('index', 'app/index')->name('admin_app_index');
        Route::any('edit/:id', 'app/edit')->name('admin_app_edit')->pattern(['id' => '\d+']);
        Route::any('create', 'app/create')->name('admin_app_create');
        Route::any('version/:id', 'app/version')->name('admin_app_version')->pattern(['id' => '\d+']);
        Route::any('version/release/:id', 'app/versionRelease')->name('admin_app_version_release')->pattern(['id' => '\d+']);
        Route::any('version/edit/:id', 'app/versionEdit')->name('admin_app_version_edit')->pattern(['id' => '\d+']);
    });

    /**
     * Card Password Route
     */
    Route::group('card', function () {
        Route::any('index', 'card/index')->name('admin_card_index');
        Route::any('create', 'card/create')->name('admin_card_create');
    });

    /**
     * Auth Route
     */
    Route::group('auth', function () {
        Route::any('index', 'auth/index')->name('admin_auth_index');
        Route::any('create', 'auth/create')->name('admin_auth_create');
        Route::any('edit', 'auth/edit')->name('admin_auth_edit');
        Route::any('log', 'auth/log')->name('admin_auth_log');
    });

    Route::any('log', 'index/log')->name('admin_log');
});


/**
 * Ajax Route
 */
Route::group('ajax', function () {
    Route::any('login', 'ajax/login')->name('admin_ajax_login');
    Route::any('getMenu', 'ajax/getMenu')->name('admin_get_menu');
    Route::any('selfEdit', 'ajax/selfEdit')->name('admin_ajax_self_edit');
    Route::any('getConsoleInfo', 'ajax/getConsoleInfo')->name('admin_get_console_info');
    Route::any('getUpdateInfo', 'ajax/getUpdateInfo')->name('admin_ajax_get_update_info');
    Route::any('selfUpdate', 'ajax/selfUpdate')->name('admin_ajax_self_update');
    Route::any('upload', 'ajax/upload')->name('admin_ajax_upload');
    Route::any('getAdminInfo', 'ajax/getAdminInfo')->name('admin_ajax_admin_info');
    Route::any('getWebsiteInfo', 'ajax/getWebsiteInfo')->name('admin_ajax_get_website_info');
    Route::any('getSiteSetting', 'ajax/getSiteSetting')->name('admin_ajax_get_site_setting');
    Route::any('editSiteSetting', 'ajax/editSiteSetting')->name('admin_ajax_edit_site_setting');
    Route::any('getApp', 'ajax/getApp')->name('admin_ajax_get_app');
    Route::any('getAppList', 'ajax/getAppList')->name('admin_ajax_get_app_list');
    Route::any('appCreate', 'ajax/appCreate')->name('admin_ajax_app_create');
    Route::any('appEdit', 'ajax/appEdit')->name('admin_ajax_app_edit');
    Route::any('appDelete', 'ajax/appDelete')->name('admin_ajax_app_delete');
    Route::any('getAppVersionList', 'ajax/getAppVersionList')->name('admin_ajax_get_app_version_list');
    Route::any('appVersionRelease', 'ajax/appVersionRelease')->name('admin_ajax_app_version_release');
    Route::any('appVersionEdit', 'ajax/appVersionEdit')->name('admin_ajax_app_version_edit');
    Route::any('appVersionDelete', 'ajax/appVersionDelete')->name('admin_ajax_app_version_delete');
    Route::any('getCardList', 'ajax/getCardList')->name('admin_ajax_get_card_list');
    Route::any('cardCreate', 'ajax/cardCreate')->name('admin_ajax_card_create');
    Route::any('cardEdit', 'ajax/cardEdit')->name('admin_ajax_card_edit');
    Route::any('cardDelete', 'ajax/cardDelete')->name('admin_ajax_card_delete');
    Route::any('getUserList', 'ajax/getUserList')->name('admin_ajax_get_user_list');
    Route::any('getUserInfo', 'ajax/getUserInfo')->name('admin_ajax_get_user_info');
    Route::any('userCreate', 'ajax/userCreate')->name('admin_ajax_user_create');
    Route::any('userEdit', 'ajax/userEdit')->name('admin_ajax_user_edit');
    Route::any('userEditStatus', 'ajax/userEditStatus')->name('admin_ajax_user_edit_status');
    Route::any('userDelete', 'ajax/userDelete')->name('admin_ajax_user_delete');
    Route::any('getUserAuth', 'ajax/getUserAuth')->name('admin_ajax_get_user_auth');
    Route::any('userAuthEdit', 'ajax/userAuthEdit')->name('admin_ajax_user_auth_edit');
    Route::any('getAuth', 'ajax/getAuth')->name('admin_ajax_get_auth');
    Route::any('getAuthList', 'ajax/getAuthList')->name('admin_ajax_get_auth_list');
    Route::any('authCreate', 'ajax/authCreate')->name('admin_ajax_auth_create');
    Route::any('authDelete', 'ajax/authDelete')->name('admin_ajax_auth_delete');
    Route::any('authEdit', 'ajax/authEdit')->name('admin_ajax_auth_edit');
    Route::any('getLogList', 'ajax/getLogList')->name('admin_ajax_get_log_list');
    Route::any('getAuthLogList', 'ajax/getAuthLogList')->name('admin_ajax_get_auth_log_list');
    Route::any('getNoticeList', 'ajax/getNoticeList')->name('admin_ajax_get_notice_list');
    Route::any('noticeCreate', 'ajax/noticeCreate')->name('admin_ajax_notice_create');
    Route::any('noticeDelete', 'ajax/noticeDelete')->name('admin_ajax_notice_delete');
    Route::any('noticeEdit', 'ajax/noticeEdit')->name('admin_ajax_notice_edit');
});