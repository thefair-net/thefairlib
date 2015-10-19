<?php
/**
 * Storage.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\DB\Redis;

class Storage extends Base
{
    public static function config($name){
        $conf = parent::config('storage');
        if(isset($conf[$name])){
            throw new Exception('Redis Conf Error');
        }else{
            return $conf[$name];
        }
    }
}