<?php
/**
 * Copyright (c) 2021 OneCodeMonkey, Inc. All Rights Reserved.
 *
 * File: YapiDocService.php
 * User: OneCodeMonkey (https://github.com/OneCodeMonkey)
 * Date: 2021/2/26
 * Time: 10:31
 */

namespace TheFairLib\Command\Wiki;

use GuzzleHttp\Exception\GuzzleException;
use TheFairLib\Exception\ServiceException;
use TheFairLib\Service\BaseService;
use Hyperf\Guzzle\ClientFactory;

class Yapi extends BaseService
{

    /**
     * @var string 创建接口
     */
    const API_ADD = "/api/interface/add";
    /**
     * @var string 更新接口
     */
    const API_UPSERT = "/api/interface/save";
    /**
     * @var string 创建分类接口
     */
    const API_CATEGORY_ADD = "/api/interface/add_cat";
    /**
     * @var string 获得分类接口
     */
    const API_CATEGORY_LIST = "/api/interface/getCatMenu";

    const API_HEADER = [
        "Content-Type" => "application/x-www-form-urlencoded",
    ];

    /**
     * @var ClientFactory
     */
    private $clientFactory;

    public function __construct(ClientFactory $clientFactory)
    {
        $this->clientFactory = $clientFactory;
    }

    public function addDoc(
        $categoryId,
        string $title,
        string $desc,
        string $path,
        $status = "undone",
        $method = "POST",
        array $reqQuery = [],
        array $reqHeaders = [],
        array $reqBodyForm = [],
        string $resBody = "",
        array $reqParams = [],
        string $message = ""
    ) {
        $url = self::API_ADD;
        $yapiConfig = config('docs.yapi');

        //验证格式
        $this->validateConfig($yapiConfig);
        $docUrlPrefix = config('docs.url_prefix');

        if (!empty($docUrlPrefix)) {
            $pathWithoutPrefix = "/";
            $pathSplit = explode('/', $path);
            foreach ($pathSplit as $pathSplitIndex => $pathSplitItem) {
                if ($pathSplitIndex == 0 || $pathSplitIndex == 1) {
                    continue;
                }
                $pathWithoutPrefix .= $pathSplitItem . '/';
            }
            $pathWithoutPrefix = rtrim($pathWithoutPrefix, '/');
        } else {
            $pathWithoutPrefix = $path;
        }

        // 是否为不更新的接口
        $notSyncUrls = config('docs.not_sync');
        if (!empty($notSyncUrls)) {
            foreach ($notSyncUrls as $notSyncUrlItem) {
                if ($notSyncUrlItem == $pathWithoutPrefix) {
                    return [
                        'errmsg' => '不更新的接口',
                    ];
                }
                // 根据 /module/controller 设置批量更新
                $notSyncUrlItem = ltrim($notSyncUrlItem, "/");
                $notSyncUrlItemArr = explode("/", $notSyncUrlItem);
                if (count($notSyncUrlItemArr) >= 2) {
                    if (strpos($pathWithoutPrefix, $notSyncUrlItem) != false) {
                        return [
                            'errmsg' => '不更新的接口',
                        ];
                    }
                }
            }
        }

        // 读取配置，是否强制更新接口，包含完全匹配，和根据 /module/controller 批量设置强制更新接口两种模式
        $forceUpdateUrls = config('docs.force_update');

        foreach ($forceUpdateUrls as $forceUpdateUrlItem) {
            if ($forceUpdateUrlItem == $pathWithoutPrefix) {
                $url = self::API_UPSERT;
                break;
            }
            // 根据 /module/controller 设置来批量更新
            $forceUpdateUrlItem = ltrim($forceUpdateUrlItem, "/");
            $forceUpdateUrlItemArr = explode('/', $forceUpdateUrlItem);
            if (count($forceUpdateUrlItemArr) >= 2) {
                if (strpos($pathWithoutPrefix, $forceUpdateUrlItem) !== false) {
                    $url = self::API_UPSERT;
                    break;
                }
            }
        }

        $params = [
            "token" => arrayGet($yapiConfig, 'project_token'),
            "title" => $title,
            "desc" => $desc,
            "path" => $path,
            "method" => $method,
            "status" => $status,
            "catid" => $categoryId,
            "req_query" => $reqQuery,
            "req_headers" => $reqHeaders,
            "req_params" => $reqParams,
            "req_body_form" => $reqBodyForm,
            "res_body_type" => 'json',
            "res_body" => '',
            "switch_notice" => false,
            "message" => $message,
        ];

        $client = $this->clientFactory->create([
            'base_uri' => arrayGet($yapiConfig, 'host'),
            'timeout' => 3.0,
        ]);
        try {
            $result = $client->post(
                $url,
                [
                    "headers" => self::API_HEADER,
                    "form_params" => $params,
                    "synchronous" => true,
                ]
            );

            return decode($result->getBody()->getContents());
        } catch (GuzzleException $e) {
            throw new ServiceException('update doc fail. ' . $e->getMessage());
        }
    }

    /**
     * 分类接口
     *
     * @param string $name
     * @param string $desc
     * @return bool
     */
    public function addCategory(string $name, string $desc = "")
    {
        $yapiConfig = config('docs.yapi');

        //验证格式
        $this->validateConfig($yapiConfig);

        $yapiProjectToken = config('docs.yapi.project_token');
        $yapiProjectId = config('docs.yapi.project_id');


        $client = $this->clientFactory->create([
            'base_uri' => arrayGet($yapiConfig, 'host'),
            'timeout' => 3.0,
        ]);

        // 判断同名 category 是否已存在
        $catListUrl = self::API_CATEGORY_LIST;
        $getListParams = [
            "project_id" => $yapiProjectId,
            "token" => $yapiProjectToken,
        ];
        $catListUrl = rtrim($catListUrl, '?') . '?';
        foreach ($getListParams as $paramName => $value) {
            $catListUrl .= $paramName . '=' . $value . '&';
        }

        try {
            $result = $client->get(
                $catListUrl,
                [
                    "headers" => self::API_HEADER,
                ]
            );
            $result = $result->getBody()->getContents();
            if (!empty($result)) {
                $categoryList = decode($result);
                foreach ($categoryList['data'] as $item) {
                    if ($item['name'] == $name) {
                        // 直接返回 categoryId
                        return $item['_id'];
                    }
                }
            }
        } catch (GuzzleException $e) {
            throw new ServiceException('add category error ' . $e->getMessage());
        }

        $addCatUrl = self::API_CATEGORY_ADD;

        $params = [
            "project_id" => $yapiProjectId,
            "token" => $yapiProjectToken,
            "name" => $name,
            "desc" => $desc,
        ];

        try {
            $addCatResult = $client->post(
                $addCatUrl,
                [
                    "headers" => self::API_HEADER,
                    "form_params" => $params,
                    "synchronous" => true,
                ]
            );

            $addCatResult = $addCatResult->getBody()->getContents();
            $addCatResult = decode($addCatResult);
            return $addCatResult['data']['_id'] ?? false;
        } catch (GuzzleException $e) {
            throw new ServiceException('add category error .' . $e->getMessage());
        }
    }

    /**
     * 验证格式
     *
     * @param array $config
     */
    protected function validateConfig(array $config): void
    {
        foreach (['host', 'project_token', 'project_id'] as $name) {
            $value = arrayGet($config, $name);
            if (empty($value)) {
                throw new ServiceException('docs config error ' . $name);
            }
        }
    }
}
