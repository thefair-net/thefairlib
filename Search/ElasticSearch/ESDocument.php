<?php
/**
 * Created by xiangc
 * Date: 2018/7/1
 * Time: 00:50
 */

namespace TheFairLib\Search\ElasticSearch;

/**
 * ES索引文档
 */
class ESDocument
{
    private $indexName = "";
    private $_data = [];
    private $id = "";

    /**
     * ESDocument constructor.
     * @param string $indexName 索引名
     */
    public function __construct($indexName)
    {
        $this->indexName = $indexName;
    }

    /**
     * 设置id
     * @param $id
     */
    public function addId($id)
    {
        $this->id = $id;
    }

    /**
     * 设置id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * 设置索引字段
     * @param $key
     * @param $value
     */
    public function addField($key, $value)
    {
        $this->_data[$key] = $value;
    }

    /**
     * 批量添加
     * @param array $data
     */
    public function addBulkFields($data)
    {
        $this->_data = array_merge($this->_data, $data);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return [
            'id' => $this->id,
            'body' => $this->_data
        ];
    }


}