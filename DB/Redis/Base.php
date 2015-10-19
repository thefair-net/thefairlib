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

    abstract public static function getInstance($name = 'default');
}