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

    public static function getOriginalAction(){
        $path = self::Instance()->_getUriInfo('path');
        return self::Instance()->_getPathInfo($path, 'filename');
    }

    public static function getRequestFormat(){
        $path = self::Instance()->_getUriInfo('path');
        return self::Instance()->_getPathInfo($path, 'extension');
    }

    private function _getPathInfo($path, $key = ''){
        $pathInfo = pathinfo($path);
        return !empty($key) ? (isset($pathInfo[$key]) ? $pathInfo[$key] : '') : $pathInfo;
    }

    private function _getUriInfo($key = ''){
        $uri = $_SERVER['REQUEST_URI'];
        $uriInfo = parse_url($uri);
        return !empty($key) ? (isset($uriInfo[$key]) ? $uriInfo[$key] : '') : $uriInfo;
    }
}