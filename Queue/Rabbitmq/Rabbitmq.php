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

use PhpAmqpLib\Message\AMQPMessage;
use TheFairLib\Config\Config;
use \PhpAmqpLib\Connection\AMQPStreamConnection;

class Rabbitmq
{
    static public $server = 'default';

    static public $instance;

    /**
     * @var AMQPStreamConnection
     */
    static private $_conn = null;

    /**
     * @return Rabbitmq
     */
    static public function Instance()
    {
        $class = get_called_class();
        if (empty(self::$instance)) {
            self::$instance = new $class();
            $config = Config::get_queue_rabbitmq(self::$server);
            self::$_conn = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['pass'], $config['vhost']);
        }
        return self::$instance;
    }

    /**
     * 生产者,如果不传msg就返回对象
     *
     * @param $queue //队列名称
     * @param $messageBody //内容
     * @param string $exchange //交换器
     * @throws \Exception
     */
    public function publish($queue, $messageBody, $exchange = 'router')
    {
        $channel = self::$_conn->channel();
        try {
            $channel->queue_declare($queue, false, true, false, false);
            $channel->exchange_declare($exchange, 'direct', false, true, false);
            $channel->queue_bind($queue, $exchange);

            $header = [
                'content_type' => 'text/plain',
                'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
            ];
            if (is_array($messageBody)) {
                $messageBody = json_encode($messageBody, JSON_UNESCAPED_UNICODE);
                $header = [
                    'content_type' => 'application/json',
                    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
                ];
            }
            $message = new AMQPMessage($messageBody, $header);
            return $channel->basic_publish($message, $exchange);
        } catch (\Exception $e) {
            $channel->close();
            self::$_conn->close();
            throw $e;
        }
    }

    /**
     * 消息费
     *
     * @param $queue
     * @param $func //回调函数
     * @param string $exchange
     * @param string $consumerTag
     * @throws \Exception
     */
    public function consumer($queue, $func, $exchange = 'router', $consumerTag = 'consumer')
    {
        $channel = self::$_conn->channel();
        try {

            $channel->queue_declare($queue, false, true, false, false);

            $channel->exchange_declare($exchange, 'direct', false, true, false);

            $channel->queue_bind($queue, $exchange);

            $channel->basic_consume($queue, $consumerTag, false, false, false, false, $func);

            while (count($channel->callbacks)) {
                $channel->wait();
            }

        } catch (\Exception $e) {
            $channel->close();
            self::$_conn->close();
            throw $e;
        }
    }

}