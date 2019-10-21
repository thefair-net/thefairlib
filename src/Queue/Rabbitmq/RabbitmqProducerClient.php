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
use TheFairLib\Model\Exception as TfException;
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
    private function closeConnection()
    {
        try {
            if (!empty(self::$instance) && $this->_conn->isConnected()) {
                $this->_conn->close();
            }
        } catch (Exception $e) {
            Logger::Instance()->error('error closeConnection: ' . $e->getMessage());
        }
        self::$instance = null;
    }

    /**
     * 全局关闭
     */
    static public function allCloseConnection()
    {
        try {
            if (!empty(self::$instance)) {
                foreach (self::$instance as $conn) {
                    if (!empty($conn->_conn) && $conn->_conn->isConnected()) {
                        $conn->_conn->close();
                    }
                }
                self::$instance = null;
            }
        } catch (Exception $e) {
            Logger::Instance()->error('error allCloseConnection: ' . $e->getMessage());
        }
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
     * @param $retry //重试次数
     * @return bool
     * @throws Exception
     */
    public function publish($queue, $messageBody, $exchange, $type, $router, $retry = 0)
    {
        // @todo 请先手动创建队列

        $header = [
            'content_type' => 'text/plain',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
        ];
        $body = $messageBody;
        if (is_array($messageBody)) {
            $body = Utility::encode($messageBody);
            $header = [
                'content_type' => 'application/json',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ];
        }
        $message = new AMQPMessage($body, $header);
        try {
            $channel = $this->channel();
            $channel->basic_publish($message, $exchange, $router);
            return true;
        } catch (Exception $e) {
            $this->closeConnection();
            if ($retry >= 3) {//重试3次
                throw new Exception("publish:" . Utility::encode([$queue, $exchange, $router, $e->getMessage(), $messageBody, $retry]));
            }
            $retry += 1;
            usleep(50000);//50毫秒
            Logger::Instance()->error("publish:" . Utility::encode([$queue, $exchange, $router, $e->getMessage(), $messageBody, $retry]));
            return $this->publish($queue, $messageBody, $exchange, $type, $router, $retry);
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
     * @param $retry //重试3次
     * @return bool
     * @throws Exception
     */
    public function publishDelay($queue, $messageBody, $delay, $exchange, $router, $retry = 0)
    {
        $header = [
            'content_type' => 'text/plain',
            'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT,
            'application_headers' => new AMQPTable([
                'delay' => strval($delay),
            ])
        ];
        $body = $messageBody;
        if (is_array($messageBody)) {
            $body = Utility::encode($messageBody);
            $header['content_type'] = 'application/json';
        }
        $message = new AMQPMessage($body, $header);
        try {
            $channel = $this->channel();
            $channel->basic_publish($message, $exchange, $router);
            return true;
        } catch (Exception $e) {
            $this->closeConnection();
            if ($retry >= 3) {
                throw new Exception("publishDelay:" . Utility::encode([$queue, $exchange, $router, $e->getMessage(), $messageBody, $retry]));
            }
            $retry += 1;
            usleep(50000);//50毫秒
            Logger::Instance()->error("publishDelay:" . Utility::encode([$queue, $exchange, $router, $e->getMessage(), $messageBody, $retry]));
            return $this->publishDelay($queue, $messageBody, $delay, $exchange, $router, $retry);
        }
    }
}
