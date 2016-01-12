<?php
/**
 * Config 类，获取各种通用配置
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Config;

use TheFairLib\Utility\Utility;

final class Config
{
    /**
     * yaf simple 配置
     */
    const YAF_SIMPLE_CLASS_TAG = 'simple';

    /**
     * yaf ini 配置
     */
    const YAF_INI_CLASS_TAG = 'ini';

    /**
     * 普通配置文件，返回一个配置对象
     */
    const NORMAL_CLASS_TAG = 'normal';

    private static $configList = array();

    /**
     * 获取yaf配置文件实例
     *
     * @param $configTag
     * @param string $type
     * @throws Exception
     */
    private static function _getInstance($configTag, $type = self::NORMAL_CLASS_TAG){

        $md5Key     = $configTag.$type;
        $md5        = md5($md5Key);
        if(isset(self::$configList[$md5])){
            $return = self::$configList[$md5];
        }else{
            if(strpos($configTag, '.') === false){
                $fileName = ucwords($configTag);
            }else{
                $pathAry            = explode('.', $configTag);
                $count              = count($pathAry);
                $pathAry[$count-1]  = ucwords($pathAry[$count-1]);
                $fileName = implode(DIRECTORY_SEPARATOR, $pathAry);
            }
            $filePath   = APP_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . $fileName . '.php';

            if(file_exists($filePath)){
                switch($type){
                    case self::NORMAL_CLASS_TAG :
                        //线上配置
                        $prodFilePath = APP_PATH . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'production' . DIRECTORY_SEPARATOR . $fileName . '.php';
                        if(file_exists($prodFilePath)){
                            $return = require $prodFilePath;
                        }else{
                            $return = require $filePath;
                        }
                        break;
                    case self::YAF_SIMPLE_CLASS_TAG :
                        $className = '\\Yaf\\Config\\'.ucwords($type);
                        $return =  new $className(require $filePath);
                        break;
                    case self::YAF_INI_CLASS_TAG :
                        $className = '\\Yaf\\Config\\'.ucwords($type);
                        $return =  new $className(require $filePath);
                        break;
                    default :
                        throw new Exception('CONFIG TYPE ERROR');
                }

                self::$configList[$md5] = $return;
            }else{
                throw new Exception('CONFIG FILE NOT FOUND'.$filePath);
            }
        }

        return $return;
    }

    /**
     * 加载配置文件
     *
     * @param string $configTag 文件目录，例如：path.to.file 解析为 APP_PATH/config/path/to/file.php
     * @param string $type 支持yaf的两种配置文件模式，simple或者ini
     * @return mixed
     * @throws Exception
     */
    public static function load($configTag, $type = self::NORMAL_CLASS_TAG){
        if(!defined('APP_PATH')){
            throw new Exception('NOT DEFINED APP PATH');
        }

        return self::_getInstance($configTag, $type);
    }

    public static function __callStatic($func, $arguments){
        $funcAry = explode('_', $func);
        if (empty($funcAry)) {
            return false;
        }
        $type = current($funcAry);
        array_shift($funcAry);
        $key = implode('.', $funcAry);

        if (!in_array($type, array('get')) || $key == '') {
            return false;
        }
        switch ($type) {
            case 'get':
                $config = self::load($key);
                return !empty($arguments[0]) ? Utility::arrayGet($config, $arguments[0]) : $config;
                break;
            default:
        }

        return null;
    }
}