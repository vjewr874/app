<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/08/17 17:41:49
 * 
 */

return [
    [
        'name' => 'home',
        'title' => '控制台',
        'icon' => 'layui-icon-home',
        'jump' => '/',
    ],
    [
        'title' => '网站管理',
        'icon' => 'layui-icon-website',
        'name' => 'site',
        'list' => [
            [
                'name' => 'admin_site_setting',
                'title' => '网站设置',
                'jump' => '/site/setting',
            ],
            [
                'name' => 'admin_site_notice',
                'title' => '公告列表',
                'jump' => '/site/notice',
            ],
        ],
    ],
    [
        'title' => '应用管理',
        'icon' => 'layui-icon-app',
        'name' => 'app',
        'list' => [
            [
                'name' => 'admin_app_index',
                'title' => '应用列表',
                'jump' => '/app/index',
            ],
            [
                'name' => 'admin_app_create',
                'title' => '创建应用',
                'jump' => '/app/create',
            ],
        ],
    ],
    [
        'title' => '用户管理',
        'icon' => 'layui-icon-username',
        'name' => 'user',
        'list' => [
            [
                'name' => 'admin_user_index',
                'title' => '用户列表',
                'jump' => '/user/index',
            ],
            [
                'name' => 'admin_user_create',
                'title' => '创建用户',
                'jump' => '/user/create',
            ],
        ],
    ],
    [
        'title' => '卡密管理',
        'icon' => 'layui-icon-card-password',
        'name' => 'card',
        'list' => [
            [
                'name' => 'admin_card_index',
                'title' => '卡密列表',
                'jump' => '/card/index',
            ],
            [
                'name' => 'admin_card_create',
                'title' => '生成卡密',
                'jump' => '/card/create',
            ],
        ],
    ],
    [
        'title' => '授权管理',
        'icon' => 'layui-icon-auz',
        'name' => 'auth',
        'list' => [
            [
                'name' => 'admin_auth_index',
                'title' => '授权列表',
                'jump' => '/auth/index',
            ],
            [
                'name' => 'admin_auth_create',
                'title' => '添加授权',
                'jump' => '/auth/create',
            ],
            [
                'name' => 'admin_auth_log',
                'title' => '查询日志',
                'jump' => '/auth/log',
            ],
        ],
    ],
    [
        'name' => 'log',
        'title' => '日志管理',
        'icon' => 'layui-icon-log',
        'jump' => '/log',
    ],
];
