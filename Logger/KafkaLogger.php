<?php
/**
 * Created by PhpStorm.
 * User: wangjianqiu
 * Date: 19/2/11
 * Time: 上午11:07
 */

namespace TheFairLib\Logger;


class KafkaLogger
{
    private static $instance = null;

    const KAFKA_NAME_START = 'db';


    /**
     * @return null|KafkaLogger
     */
    static public function Instance()
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
     * 发送队列消息
     *
     * @param $database
     * @param $table
     * @param $primary
     * @return bool
     */
    public function send($database,$table,$primary)
    {
        if (empty($database) || empty($table) || empty($primary)) {
            return false;
        }
        $request_action = $this->getRouteAction($database);
        if (empty($request_action)) {
            return false;
        }
        $send_params['database'] = $database;
        $send_params['table'] = $table;
        $send_params['primary'] = \TheFairLib\Utility\Utility::encode($primary);
        return \TheFairLib\Http\Curl()->post($request_action,$send_params);
    }
}