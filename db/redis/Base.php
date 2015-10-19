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
     * redis实例
     */
    private $redis = array();
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

    }

    public static function getInstance($name = 'default'){
        if (!isset(self::$instance[$name])) {
            self::$instance[$name] = new self;
        }

        if(!static::$configs)
        {
            static::$configs = Config::load('db.redis');
        }

        if(!self::$instance[$name]->config)
        {
            self::$instance[$name]->config = static::$configs[$name];
        }
        self::$instance[$name]->_init();
        return self::$instance[$name];
    }
}