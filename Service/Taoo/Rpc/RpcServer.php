<?php
/**
 * RpcServer.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Service\Taoo\Rpc;
use TheFairLib\Logger\Logger;
use TheFairLib\Service\Swoole\Network\Protocol\BaseServer;

class RpcServer extends BaseServer{
    public function onReceive($server, $clientId, $fromId, $data)
    {
        Logger::Instance()->info('onReceive');
        return $server->send($clientId,json_encode(array('code'=>400, 'msg'=>'bad request', 'data'=>null)));
    }

    public function onStart($serv, $workerId)
    {
        Logger::Instance()->info('onStart');
    }

    public function onShutdown($serv, $workerId)
    {
        Logger::Instance()->info('onShutdown');
    }

    public function onConnect($server, $fd, $fromId)
    {
        Logger::Instance()->info('onConnect');
    }

    public function onClose($server, $fd, $fromId)
    {
        Logger::Instance()->info('onClose');
    }

    public function onTask($serv, $taskId, $fromId, $data)
    {
        Logger::Instance()->info('onTask');
    }

    public function onFinish($serv, $taskId, $data)
    {
        Logger::Instance()->info('onFinish');
    }

    public function onTimer($serv, $interval)
    {
        Logger::Instance()->info('onTimer');
    }

    public function onRequest($request, $response)
    {
        Logger::Instance()->info('onRequest');
    }

    public function onHttpWorkInit($request, $response)
    {
        Logger::Instance()->info('onHttpWorkInit');
    }
}