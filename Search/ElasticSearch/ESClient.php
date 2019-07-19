<?php
/***************************************************************************
 *
 * Copyright (c) 2017 thefair.net.cn, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file Client.php
 * @author mingzhi(liumingzhi@thefair.net.cn)
 * @date 2017-05-26 16:00:00
 *
 **/

namespace TheFairLib\Search\ElasticSearch;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Exception;
use TheFairLib\Config\Config;
use TheFairLib\Utility\Utility;


class ESClient
{
    static public $instance;

    static protected $path = '';

    static protected $wt = 'json'; //xml

    //超时15秒
    protected $timeout = 15;

    protected $_data = [];

    protected $_keywords = '';

    static private $client = null;

    const STRICT_MODE = true;

    private $index = '';
    private $type = '';

    /**
     * 获取基础数据
     * @return array
     */
    private function _getBaseParam()
    {
        return [
            'index' => $this->index,
            'type' => $this->type,
            'client' => [
                'timeout' => $this->timeout,        // ten second timeout
                'connect_timeout' => $this->timeout
            ]
        ];
    }


    /**
     * ES 搜索
     *
     * @param string $index 索引名
     * @param string $type 索引类型
     * @param string $labelName 配置文件名称
     * @return ESClient
     */
    static public function Instance($index, $type = '', $labelName = 'default')
    {
        $class = get_called_class();
        if (empty(self::$instance[$index])) {
            self::$instance[$index] = new $class();
            if (empty($type)) {
                $type = $index;
            }
            self::$instance[$index]->index = $index;
            self::$instance[$index]->type = $type;

            $config = Config::get_search_elastic($labelName);
            $host = $config['host'];
            $port = $config['port'];

            $options = [
                'hostname' => $host,
                'port' => $port
            ];

            self::$client = ClientBuilder::create()
                ->setRetries(2)
                ->setHosts($options)
                ->build();
        }
        return self::$instance[$index];
    }

    /**
     * @return Client
     */
    public function client()
    {
        return self::$client;
    }

    /**
     * 添加或更新索引文档
     *
     * @param ESDocument $document
     * @return array    //[status] => 0 [QTime] => 11
     * @throws Exception
     */
    public function saveIndexDocument(ESDocument $document)
    {
        $params = array_merge($document->getData(), $this->_getBaseParam());

        $result = self::client()->index($params);

        $resultValue = $this->_getResultValue($result);
        if (in_array($resultValue, ['created', 'updated'])) {
            $status = true;
            $msg = 'success';
        } else {
            $status = false;
            $msg = '记录创建失败:' . Utility::encode($params);
            if (self::STRICT_MODE) {
                throw new Exception($msg);
            }
        }

        return ['status' => $status, 'msg' => $msg];
    }

    /**
     * 删除索引
     *
     * @param string $id
     * @return array
     * @throws Exception
     */
    public function deleteById($id)
    {
        $params = array_merge(['id' => $id], $this->_getBaseParam());

        $result = self::client()->delete($params);

        $resultValue = $this->_getResultValue($result);
        if (in_array($resultValue, ['deleted'])) {
            $status = true;
            $msg = 'success';
        } else {
            $status = false;
            $msg = '删除记录失败:' . Utility::encode($params);
            if (self::STRICT_MODE) {
                throw new Exception($msg);
            }
        }

        return ['status' => $status, 'msg' => $msg];
    }

    /**
     * 查询
     *
     * @param array $queryBody
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function query($queryBody, $page = 1, $limit = 20)
    {
        $pagerArray = [
            "from" => ($page - 1) * $limit,
            "size" => $limit,
        ];

        $searchBody = ["body" => array_merge($pagerArray, $queryBody)];

        $queryParams = array_merge($this->_getBaseParam(), $searchBody);

        $queryResponse = self::client()->search($queryParams);
        $ret = $this->_getPageTemplate();
        if (!empty($queryResponse)) {//如果成功，就commit
            $itemPerPage = min(50, $limit);

            $totalCount = Utility::arrayGet($queryResponse['hits'], 'total', 0);
            $hitItems = Utility::arrayGet($queryResponse['hits'], 'hits', []);
            $items = [];
            $hl = [];
            foreach ($hitItems as $hitItem) {
                $sigleItem = Utility::arrayGet($hitItem, '_source', []);
                if (!empty($sigleItem)) {
                    $items[] = $sigleItem;

                    $content = Utility::arrayGet($hitItem, "highlight");
                    if (!empty($content)) {
                        $hl[$sigleItem['id']] = $hitItem['highlight'];
                    }
                }

            }

            $pageCount = ceil($totalCount / $itemPerPage);
            $ret['page'] = $page;
            $ret['page_count'] = $pageCount;
            $ret['item_count'] = $totalCount;
            $ret['item_list'] = $items;

            $ret['highlighting'] = !empty($hl) ? $hl : [];
        }
        return $ret;
    }

    private function _getPageTemplate()
    {
        return [
            'page' => 1,
            'page_count' => 0,
            'item_count' => 0,
            'item_per_page' => 20,
            'item_list' => [],
        ];
    }

    /**
     * 获取结果中的result
     * @param array $result
     * @return string
     */
    private function _getResultValue($result)
    {
        return $result['result'];
    }

    public function exist($body)
    {
        $params = array_merge($body, $this->getBaseParam());
        return $this->client()->exists($params);
    }

    /**
     * 获取基础数据
     * @return array
     */
    public function getBaseParam()
    {
        return [
            'index' => $this->index,
            'type' => $this->type,
            'client' => [
                'timeout' => $this->timeout,        // ten second timeout
                'connect_timeout' => $this->timeout
            ]
        ];
    }
}