<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file Email.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-01-02 12:22:00
 *
 **/

return [

    'system_notice' => [
        'host' => env('EMAIL_HOST'),
        'port' => env('EMAIL_PORT'),
        'username' => env('EMAIL_USERNAME'),
        'password' => env('EMAIL_PASSWORD'),
    ],

    'system_administrator' => [
//        'liumingzhi@thefair.net.cn'
    ],

];
