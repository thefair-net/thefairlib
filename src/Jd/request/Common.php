<?php
namespace TheFairLib\Jd\request;

class Common
{
    private $apiParas = array();

    private $apiName = '';

    public function getApiMethodName()
    {
        return $this->apiName;
    }

    public function setApiMethodName($apiName)
    {
        $this->apiName = $apiName;
    }

    public function getApiParas()
    {
        return json_encode($this->apiParas);
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
        $this->apiParas[$name] = $value;
    }

    public function __get($name)
    {
        return isset($this->$name) ? $this->$name : null;
    }
}





        
 

