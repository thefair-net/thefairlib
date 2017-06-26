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

use PhpAmqpLib\Channel\AMQPChannel;
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
     * @var AMQPChannel
     */
    static private $_channel = null;


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
            self::$_channel = self::$_conn->channel();
        }
        return self::$instance;
    }

    static public function closeConnection()
    {
        if (!empty(self::$instance) && !empty(self::$_conn)) {
            self::$_channel->close();
            self::$_conn->close();
            self::$_conn = null;
            self::$_channel = null;
        }
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
     * @throws \Exception
     */
    public function publish($queue, $messageBody, $exchange, $type, $router)
    {
        try {
            self::$_channel->queue_declare($queue, false, true, false, false);
            self::$_channel->exchange_declare($exchange, $type, false, true, false);
            self::$_channel->queue_bind($queue, $exchange, $router);

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
            self::$_channel->basic_publish($message, $exchange, $router);
            return true;
        } catch (\Exception $e) {
            self::closeConnection();
        }
    }

    /**
     * 消费者
     *
     * @param $queue
     * @param $func //回调函数
     * @throws \Exception
     */
    public function consumer($queue, $func)
    {
        try {

            self::$_channel->queue_declare($queue, false, true, false, false);

            self::$_channel->basic_consume($queue, '', false, false, false, false, $func);

            while (count(self::$_channel->callbacks)) {
                self::$_channel->wait();
            }

        } catch (\Exception $e) {
            self::closeConnection();
        }
    }

}