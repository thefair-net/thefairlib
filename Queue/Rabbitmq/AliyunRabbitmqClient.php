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

use PhpAmqpLib\Channel\AMQPChannel;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Wire\AMQPTable;
use TheFairLib\Config\Config;
use \PhpAmqpLib\Connection\AMQPStreamConnection;
use TheFairLib\Utility\Utility;

class AliyunRabbitmqClient
{
    static public $instance;

    private $host;
    private $port;
    private $virtualHost;
    private static $accessKey;
    private static $accessSecret;
    private static $resourceOwnerId;

    /**
     * @var AMQPStreamConnection
     */
    static private $_conn = null;


    /**
     * @var AMQPChannel
     */
    static private $_channel = null;


    public function __construct($accessKey, $accessSecret, $resourceOwnerId = '')
    {
        self::$accessKey = $accessKey;
        self::$accessSecret = $accessSecret;
        self::$resourceOwnerId = $resourceOwnerId;
    }

    static private function getUser()
    {
        $t = '0:' . self::$resourceOwnerId . ':' . self::$accessKey;
        return base64_encode($t);
    }

    static private function getPassword()
    {
        $ts = (int)(microtime(true) * 1000);
        $value = utf8_encode(self::$accessSecret);
        $key = utf8_encode((string)$ts);
        $sig = strtoupper(hash_hmac('sha1', $value, $key, FALSE));
        return base64_encode(utf8_encode($sig . ':' . $ts));
    }

    public function getConnection()
    {
        $username = $this->getUser();
        $password = $this->getPassword();

        return new AMQPStreamConnection($this->host, $this->port,
            $username, $password,
            $this->virtualHost, false);
    }


    /**
     * Rabbitmq
     *
     * @param string $server
     * @param string $vhost
     * @return AliyunRabbitmqClient
     */
    static public function Instance($server = 'default', $vhost = '')
    {
        $class = get_called_class();
        if (empty(self::$instance) || empty(self::$_channel)) {
            $config = Config::get_queue_rabbitmq($server);
            if (empty($vhost)) $vhost = $config['vhost'];
            self::$instance = new $class($config['user'], $config['pass'], $config['resource_owner_id']);
            self::$_conn = new AMQPStreamConnection($config['host'], $config['port'], self::getUser(), self::getPassword(), $vhost);
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
            self::$_channel->basic_publish($message, $exchange, $router);
            return true;
        } catch (Exception $e) {
            self::closeConnection();
            throw new Exception($e->getMessage(), $e->getCode(), $e->getTraceAsString());

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
            if (is_array($messageBody)) {
                $messageBody = Utility::encode($messageBody);
                $header['content_type'] = 'application/json';
            }

            $message = new AMQPMessage($messageBody, $header);

            self::$_channel->basic_publish($message, $exchange, $router);
            return true;
        } catch (Exception $e) {
            self::closeConnection();
            throw new Exception($e->getMessage(), $e->getCode(), $e->getTraceAsString());
        }
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
            self::$_channel->basic_qos($qos['prefetch_size'], $qos['prefetch_count'], $qos['a_global']);

            self::$_channel->basic_consume($queue, '', false, false, false, false, $func);

            while (count(self::$_channel->callbacks)) {
                self::$_channel->wait();
            }

        } catch (Exception $e) {
            self::closeConnection();
            throw new Exception($e->getMessage(), $e->getCode(), $e->getTraceAsString());
        }
    }

    /**
     * 创建队列
     *
     * @param $queue
     * @param $exchange
     * @param $router
     * @return bool
     */
    public function queueBindRouter($queue, $exchange, $router)
    {
        self::$_channel->queue_bind($queue, $exchange, $router);
        return true;
    }

}