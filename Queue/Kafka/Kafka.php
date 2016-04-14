<?php
/**
 * Kafka.php
 *
 * @author liumingzhi
 * @version 1.0
 * @copyright 2015-2015
 * @date 16/4/14 下午2:27
 */
namespace TheFairLib\Queue\Kafka;

use Kafka\Consumer;
use Kafka\Produce;
use TheFairLib\Config\Config;
use TheFairLib\Queue\Inter;

class Kafka
{
    static public $server = 'default';

    static public $instance;

    static private $_config = [];

    /**
     * @return Kafka
     */
    static public function Instance()
    {
        $class = get_called_class();
        if (empty(self::$instance)) {
            self::$instance = new $class();
            self::$_config = Config::get_queue_kafka(self::$server);
        }
        return self::$instance;
    }

    /**
     * 生产者,如果不传msg就返回对象
     *
     * @param $topicName
     * @param array $msg
     * @return Produce
     */
    public function produce($topicName, array $msg = [])
    {
        $produce = Produce::getInstance(self::$_config['zookeeper'], self::$_config['timeout'], self::$_config['host']);
        if (empty($msg) || !is_array($msg)) {
            return $produce;
        }
        $produce->getAvailablePartitions($topicName);
        $produce->setRequireAck(-1);
        $produce->setMessages($topicName, 0, $msg);
        return $produce->send();
    }

    /**
     * 消费者
     *
     * @param $topicName
     * @return Consumer
     */
    public function consumer($topicName)
    {
        $consumer = Consumer::getInstance(self::$_config['zookeeper'], self::$_config['timeout']);
        $consumer->setGroup($topicName);
        $consumer->setFromOffset(true);
        $consumer->setTopic($topicName, 0);
        $consumer->setMaxBytes(102400);
        return $consumer;
    }

}