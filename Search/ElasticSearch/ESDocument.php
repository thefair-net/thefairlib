<?php

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
    public function addBlukFields($data)
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