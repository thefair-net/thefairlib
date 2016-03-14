<?php
namespace TheFairLib\Service\Swoole\Client;

use TheFairLib\Config\Config;

abstract class Base
{

    protected $_ip;
    protected $_port;
    protected $_syncType;
    protected $_data;
    protected $_timeout = 3;
    protected $_type;

    protected static $instance;

    public function __construct($serverTag, $syncType)
    {
        //获取服务器配置
        $config = $this->getSingleServerConfig($serverTag);
        if(empty($config['ip']) || empty($config['ip']) || empty($config['ip'])){
            throw new \Exception('Service '. $serverTag. ' Config Error!');
        }
        $this->_ip = $config['ip'];
        $this->_port = $config['port'];
        $this->_syncType = $syncType;
        $this->_timeout = $config['timeout'];
        $this->_type = $this->_getClientType();
    }

    static public function Instance($serverTag, $syncType = 'sync')
    {
        if (empty(self::$instance[$syncType][$serverTag])) {
            self::$instance[$syncType][$serverTag] = new static($serverTag, $syncType);
        }
        return self::$instance[$syncType][$serverTag];
    }

    protected function getSingleServerConfig($serverTag){
        $configList = $this->_getServerConfig($serverTag);
        return count($configList) > 1 ? $configList[array_rand($configList)] : $configList[0];
    }

    abstract public function send(array $data, callable $callback);

    abstract protected function _getClientType();

    abstract protected function _getServerConfig($serverTag);
}