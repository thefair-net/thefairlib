<?php
namespace TheFairLib\Service\Swoole\Client;

use TheFairLib\Config\Config;

class TCP extends Base
{
    public function send($data, callable $callback = NULL){
        $client = new \swoole_client(SWOOLE_SOCK_TCP, $this->_getSyncType($this->_syncType));
        if (!$client->connect($this->_ip, $this->_port, $this->_timeout)){
            exit("connect failed. Error: {$client->errCode}\n");
        }
        $client->send($data);
        $result = $client->recv();
        $client->close();

        return $result;
    }

    protected function _getClientType(){
        return 'tcp';
    }

    protected function _getServerConfig($serverTag){
        $funName = 'get_service_'.$this->_getClientType();
        return Config::$funName($serverTag);
    }

    protected function _getSyncType($syncType){
        return $syncType == 'sync' ? SWOOLE_SOCK_SYNC : SWOOLE_SOCK_ASYNC;
    }
}