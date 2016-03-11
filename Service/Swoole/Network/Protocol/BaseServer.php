<?php
namespace TheFairLib\Service\Swoole\Network\Protocol;

use Swoole;
use TheFairLib\Service\Swoole\Network\Protocol;

class BaseServer extends Protocol implements \TheFairLib\Service\Swoole\Server\Protocol
{
    public function onReceive($server, $clientId, $fromId, $data)
    {

    }

    public function onStart($serv, $workerId)
    {
    }

    public function onShutdown($serv, $workerId)
    {
    }

    public function onConnect($server, $fd, $fromId)
    {

    }

    public function onClose($server, $fd, $fromId)
    {

    }

    public function onTask($serv, $taskId, $fromId, $data)
    {

    }

    public function onFinish($serv, $taskId, $data)
    {

    }

    public function onTimer($serv, $interval)
    {

    }

    public function onRequest($request, $response)
    {

    }

    public function onHttpWorkInit($request, $response)
    {

    }
}