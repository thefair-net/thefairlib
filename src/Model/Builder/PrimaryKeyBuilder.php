<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file PrimaryKeyBuilder.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-05-24 11:03:00
 *
 **/

namespace TheFairLib\Model\Builder;


use TheFairLib\Exception\ServiceException;

class PrimaryKeyBuilder
{

    protected $primaryKey = 'id';

    protected $method;

    protected $parameters;

    protected $methods = [
        'find' => [
            'primaryKey' => 'string',
        ],
        'destroy' => [
            'primaryKey' => 'string',
        ],
        'create' => [
            'primaryKey' => 'array',
        ],
        'firstOrCreate' => [
            'primaryKey' => 'array',
        ],
    ];

    public function __construct($method, $parameters, $primaryKey)
    {
        $this->method = $method;
        $this->parameters = $parameters;
        $this->primaryKey = $primaryKey;
    }

    protected function find($id, $columns = ['*'])
    {
        return $id;//获得第一个参数做为主键
    }

    protected function create(array $attributes = [])
    {
        return $attributes[$this->primaryKey] ?? null;
    }

    /**
     * 方法会通过给定的 列 / 值 来匹配数据库中的数据。如果在数据库中找不到对应的模型， 则会从第一个参数的属性乃至第二个参数的属性中创建一条记录插入到数据库。
     *
     * @param array $attributes
     * @param array $values
     * @return mixed|null
     */
    public function firstOrCreate(array $attributes, array $values = [])
    {
        $id = $attributes[$this->primaryKey] ?? null;
        if (!empty($values) && $values[$this->primaryKey] != $id) {
            throw new ServiceException('查询的主键 ID，必须与创建的主键 ID 一致');
        }
        return $id;
    }

    public function destroy($id)
    {
        if (is_array($id)) {
            throw new ServiceException('目前不支持批量删除');
        }
        return $id;
    }

    public function __call($method, $parameters)
    {
        return $this->{$method}(...$parameters);
    }

    public function getId()
    {
        return $this->{$this->method}(...$this->parameters);
    }

    public function isMethod()
    {
        return isset($this->methods[$this->method]);
    }

}