<?php
/**
 * RpcServer.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Service\Taoo\Rpc;
use TheFairLib\Controller\Service\Error;
use TheFairLib\Exception\Service\ServiceException;
use TheFairLib\Logger\Logger;
use TheFairLib\Service\Swoole\Network\Protocol\BaseServer;
use Yaf\Application;
use Yaf\Request\Http;

class RpcServer extends BaseServer{
    /**
     * @var \Yaf\Application
     */
    protected $_application = false;
    /**
     * @param \swoole_server $server
     * @param $clientId
     * @param $fromId
     * @param $data
     * @return mixed
     */
    public function onReceive($server, $clientId, $fromId, $requestData)
    {
        try{
            ob_start();
            $requestData = $this->_decode($requestData);
            $this->_checkAuthorize($requestData['auth']);
            $data = $requestData['request_data'];
            $url = !empty($data['url']) ? $data['url'] : '';
            $_SERVER['REQUEST_URI'] = $url;
            $request = new Http($url);
            if(!empty($data['params'])){
                foreach($data['params'] as $key => $param){
                    $request->setParam($key, $param);
                    $_REQUEST[$key] = $_POST[$key] = $_GET[$key] = $param;
                }
            }
            $this->_application->getDispatcher()->catchException(true)->dispatch($request);
            $result = ob_get_contents();
            ob_end_clean();
        }catch(\Exception $e){
            if($e instanceof ServiceException){
                $ret = [
                    'code' => $e->getExtCode(),
                    'message' => $e->getMessage(),
                    'result' => (object) $e->getExtData(),
                ];
            }else{
                if(defined('APP_NAME')){
                    Logger::Instance()->error(  date("Y-m-d H:i:s +u")."\n"
                        ."请求接口:{$_SERVER['REQUEST_URI']}\n"
                        ."请求参数:".json_encode($_REQUEST)."\n"
                        ."错误信息:".$e->getMessage()."\n"
                        ."Trace:".$e->getTraceAsString()."\n\n");
                }
                $ret = [
                    'code' => 10000,
                    'message' => $e->getMessage(),
                    'result' => (object) [],
                ];
            }

            $result = json_encode($ret, JSON_UNESCAPED_UNICODE);
        }

        Logger::Instance()->info('onReceive');
        return $server->send($clientId, $this->_encode($result));
    }

    /**
     * @param \swoole_server $server
     * @param $workerId
     */
    public function onStart($server, $workerId)
    {
        //检查需要的常量是否存在
        if(!defined('APP_NAME') || !defined('APP_PATH')){
            Logger::Instance()->error('APP_NAME or APP_PATH is not defined');
            $server->shutdown();
        }
        else{
            define('APPLICATION_PATH', dirname(__DIR__));
            $this->_application = new Application(APP_PATH . "/config/application.ini");
            ob_start();
            $this->_application->bootstrap()->run();
            ob_end_clean();
        }

        Logger::Instance()->info('onStart');
    }

    /**
     * @param \swoole_server $server
     * @param $workerId
     */
    public function onShutdown($server, $workerId)
    {
        Logger::Instance()->info('onShutdown');
    }

    /**
     * @param \swoole_server $server
     * @param $fd
     * @param $fromId
     */
    public function onConnect($server, $fd, $fromId)
    {
        Logger::Instance()->info('onConnect');
    }

    /**
     * @param \swoole_server $server
     * @param $fd
     * @param $fromId
     */
    public function onClose($server, $fd, $fromId)
    {
        Logger::Instance()->info('onClose');
    }

    /**
     * @param \swoole_server $server
     * @param $taskId
     * @param $fromId
     * @param $data
     */
    public function onTask($server, $taskId, $fromId, $data)
    {
        Logger::Instance()->info('onTask');
    }

    /**
     * @param \swoole_server $server
     * @param $taskId
     * @param $data
     */
    public function onFinish($server, $taskId, $data)
    {
        Logger::Instance()->info('onFinish');
    }

    /**
     * @param \swoole_server $server
     * @param $interval
     */
    public function onTimer($server, $interval)
    {
        Logger::Instance()->info('onTimer');
    }

    /**
     * @param $request
     * @param $response
     */
    public function onRequest($request, $response)
    {
        Logger::Instance()->info('onRequest');
    }

    /**
     * @param $request
     * @param $response
     */
    public function onHttpWorkInit($request, $response)
    {
        Logger::Instance()->info('onHttpWorkInit');
    }

    protected function _encode($data){
        return base64_encode(gzcompress($data));
    }

    protected function _decode($data){
        return json_decode(gzuncompress(base64_decode($data)), true);
    }

    protected function _checkAuthorize($authData){
        if(empty($authData['app_key']) || empty($authData['app_secret'])){
            throw new ServiceException('auth config is error');
        }

        if($authData['app_secret'] != md5(md5($authData['app_key']))){
            throw new ServiceException('authorize field');
        }
        return true;
    }
}