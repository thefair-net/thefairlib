<?php
/**
 * Logger.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Logger;

class Logger
{
    private $_name = null;
    private static $instance = null;
    private $_type = null;
    public function __construct($appName)
    {
        if(!empty($appName)){
            $this->_name = $appName;
        }
    }

    static public function Instance($appName = ''){
        if(empty($appName) && defined(APP_NAME)){
            $appName = APP_NAME;
        }
        if (empty(self::$instance)) {
            self::$instance = new static($appName);
        }
        return self::$instance;
    }

    public function info($s){
        $this->_type = 'info';
        $this->output("[INFO]\t$s");
    }

    public function error($s){
        $this->_type = 'error';
        $this->output("[ERROR]\t$s");
    }

    private function output($s){
        $s = date("Y-m-d H:i:s +u").": $s\n";

        $dir = '/home/thefair/logs/www/'.date("Y-m-d").'/'.str_replace('.','/',strtolower($this->_name));
        if( !is_dir($dir) ) {
            mkdir($dir, 0777, true);
        }

        file_put_contents($dir.'/'.$this->_type.'.log',$s,FILE_APPEND|LOCK_EX);
    }

}