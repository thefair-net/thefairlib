<?php
class ZMQServer extends TheFairLib\Service\Swoole\Network\Protocol implements TheFairLib\Service\Swoole\Server\Protocol
{

    public function __construct($reciver, $sender, $timeInterval)
    {

    }

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
}
