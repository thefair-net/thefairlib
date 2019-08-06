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

class RabbitmqProducerClient
{
    static public $instance;

    private $server;

    /**
     * @var AMQPStreamConnection
     */
    private $_conn = null;

    /**
     * @var AMQPChannel|null
     */
    private $_channel = null;

    public function __construct($server)
    {
        $this->server = $server;
        $this->_conn = AliyunRabbitmqClient::Instance($server)->getConnection();
        $this->_channel = $this->_conn->channel();
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
     * Rabbitmq
     *
     * @param string $server
     * @return RabbitmqProducerClient
     */
    static public function Instance($server = 'default')
    {
        if (empty(self::$instance[$server])) {
            self::$instance[$server] = new self($server);
        }
        return self::$instance[$server];
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
     * 生产者,如果不传msg就返回对象
     *
     * @param $queue //队列名称
     * @param $messageBody //内容
     * @param string $exchange //交换器
     * @param string //$type
     * @param $router //router
     * @return bool
     * @throws Exception
     */
    public function publish($queue, $messageBody, $exchange, $type, $router)
    {
        try {
            // @todo 请先手动创建队列

            $header = [
                'content_type' => 'text/plain',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ];
            if (is_array($messageBody)) {
                $messageBody = Utility::encode($messageBody);
                $header = [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
                ];
            }
            $message = new AMQPMessage($messageBody, $header);
            $channel = $this->channel();
            $channel->basic_publish($message, $exchange, $router);
            return true;
        } catch (Exception $e) {
            $this->closeConnection();
            throw new Exception("publish:" . Utility::encode([$queue, $exchange, $router, $e->getMessage(), $messageBody]));
        }
    }

    /**
     * 生产者,如果不传msg就返回对象
     * 延迟消息，延迟消息需要注意：需要现在服务器上注册一个exchange 和queue否则不能用
     * 延迟消息队列只能fix x-delayed-message 没有其他type
     *
     * @param $queue //队列名称
     * @param $messageBody //内容
     * @param $delay
     * @param string $exchange //交换器
     * @param $router //router
     * @return bool
     * @throws Exception
     */
    public function publishDelay($queue, $messageBody, $delay, $exchange, $router)
    {

        try {
            $header = [
                'content_type' => 'text/plain',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
                'application_headers' => new AMQPTable([
                    'delay' => strval($delay),
                ])
            ];
            if (in_array($queue, ['thefair_push_message_queue']) && is_array($messageBody) && !empty($messageBody['push_id']) && $messageBody['push_id'] == 2620048470982373881) {
                Logger::Instance()->info(Utility::encode(['thefair_push_message_queue' => $queue, $messageBody, $delay, $exchange, $router]));
            }
            if (is_array($messageBody)) {
                $messageBody = Utility::encode($messageBody);
                $header['content_type'] = 'application/json';
            }
            $message = new AMQPMessage($messageBody, $header);
            $channel = $this->channel();
            $channel->basic_publish($message, $exchange, $router);
            return true;
        } catch (Exception $e) {
            $this->closeConnection();
            throw new Exception("publishDelay:" . Utility::encode([$queue, $exchange, $router, $e->getMessage(), $messageBody]));
        }
    }
}
