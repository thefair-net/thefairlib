<?php
/**
 * Error.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Controller\Page;

use TheFairLib\Controller\ErrorBase;
use \Yaf\Exception as Exception;
use Yaf\Registry;
use \Exception as E;


abstract class Error extends ErrorBase
{

    public function errorAction(E $exception)
    {
        if (Registry::get('config')->phase != 'pro') {
            echo '<pre>';
            print_r($exception);
        }
        \Yaf\Dispatcher::getInstance()->autoRender(false);
        switch ($exception->getCode()) {
            case \YAF\ERR\NOTFOUND\MODULE:
            case \YAF\ERR\NOTFOUND\CONTROLLER:
            case \YAF\ERR\NOTFOUND\ACTION:
            case \YAF\ERR\NOTFOUND\VIEW:
            case 404:
                header("Content-type: text/html; charset=utf-8");
                header("status: 404 Not Found");
                $this->display("404");
                break;
            default :
                header("Content-type: text/html; charset=utf-8");
                header("status: 500 Internal Server Error");
                if (Registry::get('config')->phase == 'pro') {
                    $this->display("500");
                } else {
                    echo $exception->getMessage();
                }
                break;
        }

    }
}