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

    const KAFKA_NAME_START = 'log';


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
     * @param $route_name
     * @return string
     */
    protected function getRouteAction($route_name)
    {
        $route = [];
        $config = \TheFairLib\Config\Config::get_api_kakfa();
        if (empty($config['inner_domain'])) {
            return fasle;
        }
        $route[] = rtrim($config['inner_domain'],'/');
        $route[] = self::KAFKA_NAME_START;
        $route[] = $route_name;
        return implode('/',$route).'/';
    }


    /**
     * 发送DB队列代理消息
     *
     * @param $database
     * @param $table
     * @param $primary
     * @return bool
     */
    public function proxyDbMessage($database,$table,$primary)
    {
        if (empty($database) || empty($table) || empty($primary) || !is_array($primary)) {
            return false;
        }
        $message['database'] = $database;
        $message['table']    = $table;
        $message['primary']  = \TheFairLib\Utility\Utility::encode($primary);
        return $this->sendProxyMessage($database,$message);
    }


    /**
     * 发送代理队列消息
     *
     * @param string $route_name
     * @param array $message
     */
    public function sendProxyMessage($route_name,$message)
    {
        $request_action = $this->getRouteAction($route_name);
        if (empty($request_action)) {
            return false;
        }
        return \TheFairLib\Http\Curl()->post($request_action,$message);
    }


    /**
     * 发送普通队列消息
     *
     * @param string $topicName
     * @param array  $message
     * @return bool
     */
    public function sendDerectMessage($topicName,$message,$group_name=self::KAFKA_NAME_START)
    {
        $config = \TheFairLib\Config\Config::get_queue_kafka($group_name);
        $produce = Produce::getInstance($config['zookeeper'],$config['timeout'],$config['host']);
        $produce->getAvailablePartitions($topicName);
        $produce->setRequireAck(-1);
        $produce->setMessages($topicName,0,$message);
        return $produce->send();
    }


    /**
     * 发送DB直接消息
     *
     * @param $database
     * @param $table
     * @param $primary
     * @return bool
     */
    public function derectDbMessage($database,$table,$primary)
    {
        if (empty($database) || empty($table) || empty($primary) || !is_array($primary)) {
            return false;
        }
        $message[] = $database;
        $message[] = $table;
        $message[] = \TheFairLib\Utility\Utility::encode($primary);
        $topicName = self::KAFKA_NAME_START.implode('',array_map('ucfirst',explode('_',$database)));
        return $this->sendDerectMessage($topicName,$message);
    }
}