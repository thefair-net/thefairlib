<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file lock.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-03-05 13:48:00
 *
 **/

return [
    'drive' => 'redis',
    'enable' => true,
    'options' => [
        'redis' => [
            'pool_name' => 'default',
            'retry_delay' => 500,//毫秒
            'retry_count' => 2,//2次
        ],
    ],

];
