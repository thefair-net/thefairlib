<?php
/**
 * Base.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\DB\Redis;

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
    public $config = array();

    final protected function getRedisInstance($name){
        $this->_init();
        $parameters = $this->config($name);
        $options = array('cluster' => 'redis');
        return new \Predis\Client($parameters, $options);

    }

    abstract protected function _init();

    abstract public function config($name);

    public static function _getConfigPath(){
        return self::$_redisConfPath;
    }

    public static function _setConfigPath($path){
        return self::$_redisConfPath = $path;
    }

    /**
     * @param string $name
     * @return \Redis
     */
    public static function getInstance($name = 'default'){
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
    public static function closeConnection(){
        if(!empty(self::$instance)){
            foreach(self::$instance as $name => $redis){
                if($redis->isConnected()){
                    $redis->disconnect();
                }
            }
        }
    }
}