<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file auth.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-03-18 18:40:00
 *
 **/

return [

    'url_blacklist' => [
        'system_reserved' => [
            'method' => [
                'show_success',
                'show_error',
                'show_result',
                '__get',
                '__set',
            ],
            'route' => [

            ],
        ],
    ],

    'url_whitelist' => [
        'route' => [//加入白名单 url 不做路由参数的强制验证
            '/',
            '/index/index',
            '/index',
        ],
    ],
];
