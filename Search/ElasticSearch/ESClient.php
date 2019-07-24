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


/**
 *
 * Class Client
 * @package TheFairLib\Search\Solr
 */
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

    private $_page = [
        'page' => 1,
        'page_count' => 0,
        'item_count' => 0,
        'item_per_page' => 20,
        'item_list' => [],
    ];

    private $index = '';
    private $type = '';

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


    /**
     * ES 搜索
     *
     * @param string $index 索引名
     * @param string $type 索引类型
     * @return ESClient
     */
    static public function Instance($index, $type = '')
    {
        $class = get_called_class();
        if (empty(self::$instance[$index])) {
            self::$instance[$index] = new $class();
            if (empty($type)) {
                $type = $index;
            }
            self::$instance[$index]->index = $index;
            self::$instance[$index]->type = $type;

            $config = Config::get_search_elastic($index);
            if (empty($config)) {
                $config = Config::get_search_elastic('default');
            }

            $options = [
                'host' => $config['host'],
                'port' => $config['port'],
                'user' => $config['user'],
                'pass' => $config['pass'],
                'scheme' => 'http',
            ];

            self::$client = ClientBuilder::create()
                ->setRetries(2)
                ->setHosts([$options])
                ->setConnectionPool('\Elasticsearch\ConnectionPool\SimpleConnectionPool', [])
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
     * @param $document
     * @return array
     * @throws Exception
     */
    public function saveIndexDocument($document)
    {
        return $this->indexDocument($document['id'], $document->getData());
    }

    /**
     * 创建文档
     * @param $id
     * @param $body
     * @param bool $refresh
     * @return array
     * @throws Exception
     */
    public function indexDocument($id, $body, $refresh = false)
    {
        $params = array_merge(['id' => $id], $body, $this->getBaseParam());

        if ($refresh) {
            $params['refresh'] = true;
        }
        $result = self::client()->index($params);

        $resultValue = $this->_getResultValue($result);
        if (in_array($resultValue, ['created', 'updated'])) {
            $status = true;
        } else {
            $status = false;
            $msg = '记录创建失败:' . Utility::encode($params);
            if (self::STRICT_MODE) {
                throw new Exception($msg);
            }
        }

        return ['status' => $status];
    }

    public function exist($body)
    {
        $params = array_merge($body, $this->getBaseParam());
        return $this->client()->exists($params);
    }

    /**
     * 删除索引
     *
     * @param string $id
     * @param bool $refresh
     * @return array
     * @throws Exception
     */
    public function deleteById($id, $refresh = false)
    {
        $params = array_merge(['id' => $id], $this->getBaseParam());
        if ($refresh) {
            $params['refresh'] = true;
        }

        $result = self::client()->delete($params);

        $resultValue = $this->_getResultValue($result);
        if (in_array($resultValue, ['deleted'])) {
            $status = true;
        } else {
            $status = false;
            $msg = '删除记录失败:' . Utility::encode($params);
            if (self::STRICT_MODE) {
                throw new Exception($msg);
            }
        }

        return ['status' => $status];
    }

    /**
     * 删除所有索引文档
     *
     * @return array
     * @throws Exception
     */
    public function deleteAllDocuments()
    {
        $deleteCount = 0;
        try {

            $result = self::client()->deleteByQuery(array_merge($this->getBaseParam(), ["body" => [
                "query" => [
                    "match_all" => []
                ]
            ]]));

            $deleteCount = $result['total'];
            if ($deleteCount > 0) {
                $status = true;
            } else {
                $status = false;
                $msg = '删除所有记录失败';
                if (self::STRICT_MODE) {
                    throw new Exception($msg);
                }
            }
        } catch (Exception $e) {
            $message = Utility::decode($e->getMessage());
            if (!empty($message) && $message['result'] == 'not_found') {
                $status = true;
            } else {
                $status = false;
            }
        }

        return ['status' => $status, 'delete_count' => $deleteCount];
    }

    public function getDocumentById($id)
    {
        $params = array_merge($this->getBaseParam(), ['id' => $id]);
        return $this->client()->get($params);
    }

    public function getDocumentByIdOrEmpty($id)
    {
        $params = array_merge($this->getBaseParam(), ['id' => $id]);
        try {
            return $this->client()->get($params);
        } catch (Exception $e) {
            return [];
        }
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

        $queryParams = array_merge($this->getBaseParam(), $searchBody);
        $queryResponse = self::client()->search($queryParams);
        $ret = $this->_getPageTemplate();
        if (!empty($queryResponse)) {//如果成功，就commit
            $itemPerPage = min(500, $limit);

            $totalCount = Utility::arrayGet($queryResponse['hits'], 'total', 0);
            $hitItems = Utility::arrayGet($queryResponse['hits'], 'hits', []);
            $items = [];
            $hl = [];
            foreach ($hitItems as $hitItem) {
                $sigleItem = Utility::arrayGet($hitItem, '_source', []);
                if (!empty($sigleItem)) {
                    $items[] = $sigleItem;
                    $h = Utility::arrayGet($hitItem, "highlight");
                    if (!empty($h)) {
                        $hl[$sigleItem['id']] = $hitItem['highlight'];
                    }
                }
            }

            $pageCount = ceil($totalCount / $itemPerPage);
            $ret['page'] = $page;
            $ret['page_count'] = $pageCount;
            $ret['item_count'] = intval($totalCount);
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


}