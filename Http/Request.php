<?php
/**
 * Request.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Http;

class Request{
    private static $_instance = null;
    static public function Instance()
    {
        if (empty(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    public function getOriginalAction(){
        return self::Instance()->_getPathInfo('filename');
    }

    public static function getRequestFormat(){
        return self::Instance()->_getPathInfo('extension');
    }

    private function _getPathInfo($key = ''){
        $uri = $_SERVER['REQUEST_URI'];
        $pathInfo = pathinfo($uri);
        return !empty($key) ? (isset($pathInfo[$key]) ? $pathInfo[$key] : '') : $pathInfo;
    }
}