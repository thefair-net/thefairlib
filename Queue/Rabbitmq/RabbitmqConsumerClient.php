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

use Exception;
use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use \PhpAmqpLib\Connection\AMQPStreamConnection;
use TheFairLib\Logger\Logger;
use TheFairLib\Utility\Utility;

class RabbitmqConsumerClient
{
    static public $instance;

    private $server;

    /**
     * @var AMQPStreamConnection
     */
    private $_conn = null;


    /**
     * @var AMQPChannel
     */
    private $_channel = null;


    public function __construct($server)
    {
        $this->server = $server;
        $this->_conn = AliyunRabbitmqClient::Instance($server)->getConnection();
        $this->_channel = $this->_conn->channel();
    }

    /**
     * Rabbitmq
     *
     * @param string $server
     * @return RabbitmqConsumerClient
     */
    static public function Instance($server = 'default')
    {
        if (empty(self::$instance[$server])) {
            self::$instance[$server] = new self($server);
        }
        return self::$instance[$server];
    }

    /**
     * 结束
     *
     * @throws Exception
     */
    public function __destruct()
    {
        $this->closeConnection();
    }

    /**
     * 关闭
     *
     * @throws Exception
     */
    public function closeConnection()
    {
        try {
            if (!empty($this->_conn) && $this->_conn->isConnected()) {
                $this->_conn->close();
            }
        } catch (Exception $e) {
            Logger::Instance()->error('error closeConnection: ' . $e->getMessage());
        }
        self::$instance = null;
    }

    /**
     * 通道
     *
     * @return AMQPChannel
     * @throws Exception
     */
    protected function channel()
    {
        try {
            if (!$this->_conn->isConnected() || empty($this->_channel)) {
                Logger::Instance()->error('error isConnected');
                $this->_conn = AliyunRabbitmqClient::Instance($this->server)->getConnection();
                $this->_channel = $this->_conn->channel();
            }
        } catch (Exception $e) {//报错就重新连接
            Logger::Instance()->info('re_conn: ' . $e->getMessage());
            $this->_conn = AliyunRabbitmqClient::Instance($this->server)->getConnection();
            $this->_channel = $this->_conn->channel();
        }
        return $this->_channel;
    }

    /**
     * 消费者
     *
     * @param $queue
     * @param $exchange
     * @param $router
     * @param $func
     * @param $qos // prefetch_count：预读取消息的数量  a_global false 单独应用于信道上的每个新消费者
     * @throws Exception
     */
    public function consumer($queue, $exchange, $router, $func, array $qos = [])
    {
        try {
            if (empty($qos)) {
                $qos = [
                    'prefetch_size' => 0,
                    'prefetch_count' => 30,
                    'a_global' => false
                ];
            }

            $channel = $this->channel();

            $channel->basic_qos($qos['prefetch_size'], $qos['prefetch_count'], $qos['a_global']);

            $channel->basic_consume($queue, '', false, false, false, false, $func);

            while (count($channel->callbacks)) {
                $channel->wait();
            }

            $this->closeConnection();
        } catch (Exception $e) {
            $this->closeConnection();
            throw new Exception("consumer: $queue, $exchange, $router" . $e->getMessage());
        }
    }
}