<?php
namespace TheFairLib\Service\Swoole\Client;

abstract class Base
{

    protected $_ip;
    protected $_port;
    protected $_syncType;
    protected $_data;
    protected $_timeout = 3;
    protected $_type;
    protected $_config;
    protected $_serverTag;

    protected static $instance;

    public function __construct($serverTag, $syncType)
    {
        //获取服务器配置
        $this->_config = $this->_getServiceConfig($serverTag);
        $config = $this->getSingleServerConfig($serverTag);
        if(empty($config['ip']) || empty($config['ip']) || empty($config['ip'])){
            throw new \Exception('Service '. $serverTag. ' Config Error!');
        }
        $this->_serverTag = $serverTag;
        $this->_ip = $config['ip'];
        $this->_port = $config['port'];
        $this->_syncType = $syncType;
        $this->_timeout = $config['timeout'];
        $this->_type = $this->_getClientType();

        return $this;
    }

    /**
     * @param $serverTag
     * @param string $syncType
     * @return $this
     */
    static public function Instance($serverTag, $syncType = 'sync')
    {
        if (empty(self::$instance[$syncType][$serverTag])) {
            self::$instance[$syncType][$serverTag] = new static($serverTag, $syncType);
        }
        return self::$instance[$syncType][$serverTag];
    }

    protected function getSingleServerConfig($serverTag){
        $configList = $this->_getServerList($serverTag);
        return count($configList) > 1 ? $configList[array_rand($configList)] : $configList[0];
    }

    abstract public function send($data, callable $callback);

    abstract protected function _getClientType();

    abstract protected function _getServiceConfig($serverTag);

    abstract protected function _getServerList($serverTag);

    public function getServerTag(){
        return $this->_serverTag;
    }
}