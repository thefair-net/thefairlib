<?php
/**
 * Base.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\DB\Redis;

use TheFairLib\Config\Config;

class Base
{
    /*
     * 单例
     */
    private static $instance;

    protected static $configs;
    /*
     * redis配置
     */
    protected static $_redisConfPath = 'db.redis';
    /*
     * config
     */
    public $config = array();

    final public function __construct($parameters){
        $this->_init();
        $options = array('cluster' => 'redis');
        return new \Predis\Client($parameters, $options);

    }

    protected function _init(){

    }

    public static function config($name){
        $config = Config::load(self::_getConfigPath());
        return $config->$name;
    }

    public static function _getConfigPath(){
        return self::$_redisConfPath;
    }

    public static function _setConfigPath($path){
        return self::$_redisConfPath = $path;
    }

    public static function getInstance($name = 'default'){
        if (!isset(self::$instance[$name])) {
            self::$instance[$name] = new self(self::config($name));
        }

        return self::$instance[$name];
    }
}