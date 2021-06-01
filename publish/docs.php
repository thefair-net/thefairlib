<?php
/***************************************************************************
 *
 * Copyright (c) 2021 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file docs.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2021-02-22 13:48:00
 *
 **/

return [
    // 是否开启
    'enable' => env('DOCS_ENABLE', false),

    'force' => env('DOCS_FORCE', false),

    'not_sync' => [
        //
    ],

    'force_update' => [
        '/v1/test',
        '/test/test/test_add_category',     // 完整三级路径
//        '/test/test',       // 对 /module/controller  下的所有接口都生效
    ],

    // 保持更新 response 文件的 routes
    'refresh_response_file' => [
        // 可以完整 path ( /module/controller/method )，可以配 /module/controller 批量生效
    ],

    'url_prefix' => env('URL_PREFIX', ''), //如 /v2/user/push/save_push_info

    // 返回结果采集
    'response_result_gather_sharding' => env('DOC_SHARDING', 100), //(time() % 1000 === 0),

    'yapi' => [
        'host' => '',
        'project_token' => '',
        'project_id' => 0,
    ],
];