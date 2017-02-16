<?php
namespace TheFairLib\Service\Swoole\Client;

use TheFairLib\Config\Config;

class TCP extends Base
{
    public function send($data, callable $callback = NULL)
    {
        $client = new \swoole_client(SWOOLE_SOCK_TCP, $this->_getSyncType($this->_syncType));
        $client->set([
            'open_length_check' => true,
            'package_length_type' => 'N',
            'package_length_offset' => 0,       //第N个字节是包长度的值
            'package_body_offset' => 4,       //第几个字节开始计算长度
            'package_max_length' => 1024 * 1024 * 10,  //协议最大长度
        ]);
        if (!$client->connect($this->_ip, $this->_port, $this->_timeout)) {
            exit("connect failed. Error: {$client->errCode}\n");
        }
        $client->send($data);
        $result = $client->recv();
        $client->close();

        return $result;
    }

    protected function _getClientType()
    {
        return 'tcp';
    }

    protected function _getServiceConfig($serverTag)
    {
        $funName = 'get_service_' . $this->_getClientType();
        return Config::$funName($serverTag);
    }

    protected function _getSyncType($syncType)
    {
        return $syncType == 'sync' ? SWOOLE_SOCK_SYNC : SWOOLE_SOCK_ASYNC;
    }

    protected function _getServerList($serverTag)
    {
        return !empty($this->_config['server_list']) ? $this->_config['server_list'] : [];
    }
}