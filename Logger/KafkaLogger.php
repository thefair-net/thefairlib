<?php
/**
 * Created by PhpStorm.
 * User: wangjianqiu
 * Date: 19/2/11
 * Time: 上午11:07
 */

namespace TheFairLib\Logger;

use Kafka\Produce;
use TheFairLib\Queue\Inter;


class KafkaLogger
{
    private static $instance = null;

    const KAFKA_NAME_START = 'db';
    const KAFKA_GROUP_SERVER = 'log';


    /**
     * @return null|KafkaLogger
     */
    public static function Instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::Instance;
    }


    /**
     * 获取路由地址
     *
     * @param $database
     * @return string
     */
    protected function getRouteAction($database)
    {
        $route = [];
        $config = \TheFairLib\Config\Config::get_api_kakfa();
        if (empty($config['inner_domain'])) {
            return fasle;
        }
        $route[] = rtrim($config['inner_domain'],'/');
        $route[] = self::KAFKA_NAME_START;
        $route[] = $database;
        return implode('/',$route).'/';
    }


    /**
     * 发送队列消息@代理请求
     *
     * @param $database
     * @param $table
     * @param $primary
     * @return bool
     */
    public function proxyMessage($database,$table,$primary)
    {
        if (empty($database) || empty($table) || empty($primary) || !is_array($primary)) {
            return false;
        }
        $request_action = $this->getRouteAction($database);
        if (empty($request_action)) {
            return false;
        }
        $message['database'] = $database;
        $message['table'] = $table;
        $message['primary'] = \TheFairLib\Utility\Utility::encode($primary);
        return \TheFairLib\Http\Curl()->post($request_action,$message);
    }


    /**
     * 发送队列消息@直接入队列
     *
     * @param $database
     * @param $table
     * @param $primary
     * @return bool
     */
    public function derectMessage($database,$table,$primary)
    {
        if (empty($database) || empty($table) || empty($primary) || !is_array($primary)) {
            return false;
        }
        $config = \TheFairLib\Config\Config::get_queue_kafka(self::KAFKA_GROUP_SERVER);
        $message['database'] = $database;
        $message['table'] = $table;
        $message['primary'] = $primary;
        $message = \TheFairLib\Utility\Utility::encode($message);
        $topicName = self::KAFKA_NAME_START.implode('',array_map('ucfirst',explode('_',$database)));
        $produce = Produce::getInstance($config['zookeeper'],$config['timeout'],$config['host']);
        $produce->getAvailablePartitions($topicName);
        $produce->setRequireAck(-1);
        $produce->setMessages($topicName,0,[$message]);
        return $produce->send();
    }
}