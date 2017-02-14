<?php
namespace TheFairLib\Jd\request;

class LdopReceivePickuporderReceive
{
    private $apiParas = array();

    public function getApiMethodName()
    {
        return "jingdong.ldop.receive.pickuporder.receive";
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





        
 

