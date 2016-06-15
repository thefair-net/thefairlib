<?php
/**
 * Request.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Http;

use Yaf\Request_Abstract;

class Request extends Request_Abstract{
    public static function getOriginalAction(){
        $uri = self::getRequestUri();
        $uriAry = explode(DIRECTORY_SEPARATOR, $uri);
        return end($uriAry);
    }
}