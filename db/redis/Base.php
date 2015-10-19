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

    final public function __construct(){
        $this->_init();
    }

    protected function _init(){

    }

    public static function config($name){
        $config = Config::load('db.redis');
        return $config->$name;
    }

    public function _getConfigPath(){
        return self::$_redisConfPath;
    }

    public function _setConfigPath($path){
        self::$_redisConfPath = $path;
    }

    public static function getInstance($name = 'default'){
        if (!isset(self::$instance[$name])) {
            self::$instance[$name] = new self;
        }

        if(!self::$instance[$name]->config)
        {
            self::$instance[$name]->config = self::config($name);
        }
        self::$instance[$name]->_init();
        return self::$instance[$name];
    }
}