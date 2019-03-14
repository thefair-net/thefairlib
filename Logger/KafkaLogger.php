<?php
/**
 * Created by PhpStorm.
 * User: wangjianqiu
 * Date: 19/2/11
 * Time: 上午11:07
 */

namespace TheFairLib\Logger;

use Kafka\Producer;
use Kafka\ProducerConfig;
use TheFairLib\Config\Config;
use TheFairLib\Utility\Utility;


class KafkaLogger
{

    private static $instance = null;

    const KAFKA_NAME_START = 'log';


    /**
     * @return null|KafkaLogger
     */
    public static function Instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 发送普通队列消息.
     *
     * @param $topicName
     * @param $message
     * @param string $groupName
     */
    public function sendDirectMessage($topicName, $message, $groupName = self::KAFKA_NAME_START)
    {
        $kafkaConf = Config::get_queue_kafka($groupName);

        $config = ProducerConfig::getInstance();
        $config->setMetadataRefreshIntervalMs(10000);
        $config->setMetadataBrokerList($kafkaConf['host']);
        $config->setBrokerVersion('0.9.0.1');
        $config->setRequiredAck(0);
        $config->setIsAsyn(false);
        $config->setProduceInterval(500);
        $producer = new Producer();
        return $producer->send(array(
            array(
                'topic' => $topicName,
                'value' => $message,
                'key' => '',
            )
        ));
    }

    /**
     * 发送DB直接消息
     *
     * @param $database
     * @param $table
     * @param $primary
     */
    public function directDbMessage($database, $table, $primary)
    {
        if (empty($database) || empty($table) || empty($primary) || !is_array($primary)) {
            return;
        }
        $message[] = $database;
        $message[] = $table;
        $message[] = Utility::encode($primary);
        $topicName = self::KAFKA_NAME_START . implode('', array_map('ucfirst', explode('_', $database)));
        $this->sendDirectMessage($topicName, $message);
    }

}