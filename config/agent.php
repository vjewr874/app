<?php

/**
 *  源码来自欧皇源码分享.
 * 
 * Created time 2019/11/20 20:39:08
 * 
 */

return [
    [
        'name' => '三级代理',
        'purview' => ['create_auth'],
        'params' => [
            'create' => [],
        ],
    ],
    [
        'name' => '二级代理',
        'purview' => ['create_agent','create_auth', 'create_card'],
        'params' => [
            'create' => [0],
        ],
    ],
    [
        'name' => '一级代理',
        'purview' => ['create_agent', 'create_auth', 'create_card'],
        'params' => [
            'create' => [0, 1],
        ],
    ]
];