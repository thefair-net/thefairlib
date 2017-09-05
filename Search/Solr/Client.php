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

namespace TheFairLib\Search\Solr;

use TheFairLib\Config\Config;
use Yaf\Exception;


/**
 * 说明文档 http://php.net/manual/en/book.solr.php
 *
 * Class Client
 * @package TheFairLib\Search\Solr
 */
class Client
{
    static public $instance;

    //默认服务器
    static private $host = '127.0.0.1';

    //默认端口
    static private $port = 8983;

    static protected $path = '';

    static protected $wt = 'json'; //xml

    //超时15秒
    protected $timeout = 15;

    protected $_data = [];

    protected $_keywords = '';

    static private $client = null;

    private $_page = [
        'page' => 1,
        'page_count' => 0,
        'item_count' => 0,
        'item_per_page' => 20,
        'item_list' => [],
    ];


    /**
     * Solr 搜索
     *
     * @param $core
     * @return Client
     */
    static public function Instance($core)
    {
        $class = get_called_class();
        if (empty(self::$instance[$core])) {
            self::$instance[$core] = new $class();
            $config = Config::get_search_solr($core);
            self::$host = $config['host'];
            self::$port = $config['port'];
            self::$path = $config['path'];
            self::$wt = $config['wt'];
            $options = [
                'hostname' => self::$host,
                'port' => self::$port,
                'path' => self::$path,
                'wt' => self::$wt,
            ];
            self::$client = new \SolrClient($options);
        }
        return self::$instance[$core];
    }

    public function client()
    {
        return self::$client;
    }

    /**
     * 添加或更新索引文档
     *
     * @param $document //SolrInputDocument 对象
     * @return array    //[status] => 0 [QTime] => 11
     * @throws Exception
     */
    public function saveIndexDocument($document)
    {
        try {
            $updateResponse = self::$client->addDocument($document, true, 1000);//返回 SolrUpdateResponse 对象
            if ($updateResponse->success()) {//如果成功，就commit
                self::$client->commit();
                return (array)$updateResponse->getResponse()->responseHeader;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return [];
    }

    /**
     * 删除索引
     *
     * @param $id //可以为数组  [1,2,3,4]
     * @return array
     * @throws Exception
     */
    public function deleteById($id)
    {
        try {
            $ids = [];
            switch (true) {
                case is_array($id) :
                    $ids = $id;
                    break;
                default :
                    $ids[] = $id;
                    break;
            }
            $updateResponse = self::$client->deleteByIds($ids);//返回 SolrUpdateResponse 对象

            if ($updateResponse->success()) {//如果成功，就commit
                self::$client->commit();
                return (array)$updateResponse->getResponse()->responseHeader;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return [];
    }

    /**
     * 删除查询条件的索引    //*:*为删除所有索引
     *
     * @param $query
     * @param bool $force //强制删除
     * @return array
     * @throws Exception
     */
    public function deleteByQuery($query, $force = false)
    {
        try {
            $query = trim($query);
            if (in_array($query, ['*:*']) && !$force) {
                throw new Exception('不允许删除此条的索引');
            }
            $updateResponse = self::$client->deleteByQuery($query);//返回 SolrUpdateResponse 对象
            if ($updateResponse->success()) {//如果成功，就commit
                self::$client->commit();
                return (array)$updateResponse->getResponse()->responseHeader;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return [];
    }

    /**
     * 查询
     *
     * @param $query
     * @param int $page
     * @param int $limit
     * @return array
     * @throws Exception
     */
    public function query($query, $page = 1, $limit = 20)
    {
        try {
            if (!is_a($query, 'SolrQuery')) {
                throw new Exception('query必须是一个SolrParams对象');
            }
            $start = $query->getStart();
            if (empty($start)) {
                $offset = ($page - 1) * $limit;
                $query->setStart($offset);
                $query->setRows($limit);
            }

            $queryResponse = self::$client->query($query);//返回 SolrQueryResponse 对象
            if ($queryResponse->success()) {//如果成功，就commit
                $result = (array)$queryResponse->getResponse()->response;
                $itemPerPage = min(50, $limit);
                $pageCount = ceil($result['numFound'] / $itemPerPage);
                $this->_page['page'] = $page;
                $this->_page['page_count'] = $pageCount;
                $this->_page['item_count'] = $result['numFound'];
                $this->_page['item_list'] = $result['docs'];
                $hl = $queryResponse->getResponse()->highlighting;
                $this->_page['highlighting'] = !empty($hl) ? $hl : [];
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return $this->_page;
    }


}