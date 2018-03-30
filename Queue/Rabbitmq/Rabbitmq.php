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
use TheFairLib\Utility\Utility;

class Rabbitmq
{
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
     * Rabbitmq
     *
     * @param string $server
     * @param string $vhost
     * @return Rabbitmq
     */
    static public function Instance($server = 'default', $vhost = '')
    {
        $class = get_called_class();
        if (empty(self::$instance) || empty(self::$_channel)) {
            self::$instance = new $class();
            $config = Config::get_queue_rabbitmq($server);
            if (empty($vhost)) $vhost = $config['vhost'];
            self::$_conn = new AMQPStreamConnection($config['host'], $config['port'], $config['user'], $config['pass'], $vhost);
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
            self::$instance = null;
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
                $messageBody = Utility::encode($messageBody);
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
            throw new \Exception($e->getMessage(), $e->getCode(), $e->getTraceAsString());

        }
    }

    /**
     * 消费者
     *
     * @param $queue
     * @param $exchange
     * @param $router
     * @param $func
     * @throws \Exception
     */
    public function consumer($queue, $exchange, $router, $func)
    {
        try {

            self::$_channel->queue_declare($queue, false, true, false, false);

            self::$_channel->basic_consume($queue, '', false, false, false, false, $func);

            self::$_channel->queue_bind($queue, $exchange, $router);

            while (count(self::$_channel->callbacks)) {
                self::$_channel->wait();
            }

        } catch (\Exception $e) {
            self::closeConnection();
            throw new \Exception($e->getMessage(), $e->getCode(), $e->getTraceAsString());
        }
    }

}