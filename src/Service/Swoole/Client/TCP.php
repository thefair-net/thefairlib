<?php

namespace TheFairLib\Service\Swoole\Client;

use Exception;
use Swoole\Client;
use TheFairLib\Config\Config;
use TheFairLib\Exception\Service\RetryException;
use TheFairLib\Exception\Service\ServiceException;

class TCP extends Base
{

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var bool
     */
    protected $isConnected = false;

    /**
     * 单连接请求
     *
     * @throws ServiceException
     */
    protected function connect(): void
    {
        if ($this->isConnected && $this->client->isConnected()) {
            return;
        }
        if ($this->client) {
            $this->isConnected = false;
            $this->client->close();
            unset($this->client);
        }
        $client = new Client(SWOOLE_SOCK_TCP, $this->_getSyncType($this->_syncType));
        $client->set([
            'open_length_check' => true,
            'package_length_type' => 'N',
            'package_length_offset' => 0,       //第N个字节是包长度的值
            'package_body_offset' => 4,       //第几个字节开始计算长度
            'package_max_length' => 1024 * 1024 * 10,  //协议最大长度
        ]);
        if (!$client->connect($this->_ip, $this->_port, $this->_timeout)) {
            throw new ServiceException(sprintf("connect failed. code: %d", $client->errCode), [
                'ip' => $this->_ip,
                'port' => $this->_port,
                'timeout' => $this->_timeout,
            ]);
        }
        $this->client = $client;
        $this->isConnected = true;
    }

    public function __destruct()
    {
        $this->isConnected = false;
        $this->client->close();
    }

    /**
     * 发送
     *
     * @param $data
     * @param callable|NULL $callback
     * @throws ServiceException
     */
    public function send($data, callable $callback = NULL)
    {
        $this->connect();
        $this->client->send($data);
    }

    public function recv()
    {
        $data = $this->client->recv();
        if ($data === '') {
            $this->client->close();
            $this->isConnected = false;
            $this->client = null;
            throw new RetryException('Connection is closed.');
        }
        if ($data === false) {
            throw new RetryException('Error receiving data, errno=' . $this->client->errCode . ' errmsg=' . swoole_strerror($this->client->errCode));
        }
        return $data;
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