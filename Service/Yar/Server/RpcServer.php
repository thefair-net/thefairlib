<?php
/**
 * RpcServer.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */

namespace TheFairLib\Service\Yar\Server;
use Yaf;
class RpcServer
{
    public function run($params=array()){
        $app  = new Yaf\Application(APP_PATH . "/config/application.ini");

        $uri = Yaf\Dispatcher::getInstance()->getRequest()->getRequestUri();
        list($tmp,$module,$controller,$action) = explode('/', $uri);

        foreach ($params as $key => $value) {
            Yaf\Dispatcher::getInstance()->getRequest()->setParam($key,$value);
        }

        $request = new Yaf\Request\Simple("", $module, $controller, $action, $params);


        $response = $app->bootstrap()->getDispatcher()->returnResponse(false)->dispatch($request);



        return $response->getBody();
    }

    public static function start(){
        $server = new \Yar_Server(new static());
        $server->handle();
    }
}