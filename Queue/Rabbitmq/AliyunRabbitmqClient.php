<?php
/***************************************************************************
 *
 * Copyright (c) 2017 thefair.net.cn, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file Rabbitmq.php
 * @author mingzhi(liumingzhi@thefair.net.cn)
 * @date 2017-06-21 11:00:00
 *
 **/

namespace TheFairLib\Queue\Rabbitmq;

use TheFairLib\Config\Config;
use \PhpAmqpLib\Connection\AMQPStreamConnection;

class AliyunRabbitmqClient
{
    /**
     * @var $this
     */
    static public $instance;

    private $accessKey;
    private $accessSecret;
    private $resourceOwnerId;
    private $config;


    public function __construct($server)
    {
        $this->config = Config::get_queue_rabbitmq($server);
        $this->accessKey = $this->config['user'];
        $this->accessSecret = $this->config['pass'];
        $this->resourceOwnerId = $this->config['resource_owner_id'];
    }

    private function getUser()
    {
        $t = '0:' . $this->resourceOwnerId . ':' . $this->accessKey;
        return base64_encode($t);
    }

    private function getPassword()
    {
        $ts = (int)(microtime(true) * 1000);
        $value = utf8_encode($this->accessSecret);
        $key = utf8_encode((string)$ts);
        $sig = strtoupper(hash_hmac('sha1', $value, $key, FALSE));
        return base64_encode(utf8_encode($sig . ':' . $ts));
    }

    /**
     * Rabbitmq
     *
     * @param string $server
     * @return AliyunRabbitmqClient
     */
    static public function Instance($server = 'default')
    {
        if (empty(self::$instance[$server])) {
            self::$instance[$server] = new self($server);
        }
        return self::$instance[$server];
    }

    /**
     * AMQPStreamConnection
     *
     * @return AMQPStreamConnection
     */
    public function getConnection()
    {
        return (new AMQPStreamConnection($this->config['host'], $this->config['port'], $this->getUser(), $this->getPassword(), $this->config['vhost'], $insist = false,
            $login_method = 'AMQPLAIN',
            $login_response = null,
            $locale = 'en_US',
            $connection_timeout = 30,
            $read_write_timeout = 130.0,
            $context = null,
            $keepalive = true,
            $heartbeat = 60,
            $channel_rpc_timeout = 3));
    }

}