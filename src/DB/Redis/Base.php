<?php

/**
 * Base.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */

namespace TheFairLib\DB\Redis;

use RedisClusterException;
use TheFairLib\Db\Exception;

abstract class Base
{
    /*
     * 单例
     */
    protected static $instance;

    protected static $configs;
    /*
     * redis配置
     */
    protected static $_redisConfPath = 'db.redis';
    /*
     * config
     */
    public $config = [];

    /**
     * @param $name
     * @return RedisCluster
     * @throws RedisClusterException
     */
    final protected function getRedisInstance($name)
    {
        $this->_init();
        $parameters = $this->config($name);

        if (empty($parameters)) {
            throw new Exception("DB config not found: {$name}");
        }

        // todo add memcache
        switch ($parameters['driver']) {
            case 'redis':
                $instance = $parameters['cluster']['enable'] ? new RedisCluster($parameters) : new RedisClient($parameters);
                break;

            default:
                throw new Exception("Unknown DB driver: {$name}/{$parameters['driver']}");
                break;
        }

        return $instance;
    }

    abstract protected function _init();

    abstract public function config($name);

    public static function _getConfigPath()
    {
        return self::$_redisConfPath;
    }

    public static function _setConfigPath($path)
    {
        return self::$_redisConfPath = $path;
    }

    /**
     * @param string $name
     * @return \Redis
     */
    public static function getInstance($name = 'default')
    {
        if (!isset(self::$instance[$name])) {
            $class = get_called_class();
            $base = new $class();
            self::$instance[$name] = $base->getRedisInstance($name);
        }

        return self::$instance[$name];
    }

    /**
     * 关闭redis连接
     * 用于service处理结束后手动关闭数据服务的连接
     */
    public static function closeConnection()
    {
        if (!empty(self::$instance)) {
            try {
                foreach (self::$instance as $name => $redis) {
                    $redis->disconnect();
                }
            } catch (\Throwable $e) {
            } catch (\Exception $e) {
            } catch (\Error $e) {
            }
        }
    }
}
