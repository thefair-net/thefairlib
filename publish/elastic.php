<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @fileenv()'ELASTIC_USERNAMEsearch.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-08-25 17:46:00
 *
 **/

return [

    'default' => [
        'host' => env('ELASTIC_HOST', ''),
        'port' => 9200,
        'user' => env('ELASTIC_USERNAME'),
        'pass' => env('ELASTIC_PASSWORD'),
        'pool' => [
            'min_connections' => 10,
            'max_connections' => 100,
            'connect_timeout' => 5.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
        ],
    ],

];
