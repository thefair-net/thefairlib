<?php
namespace TheFairLib\Service\Swoole\Network;

class HttpServer extends \TheFairLib\Service\Swoole\Network\TcpServer
{
    public function init()
    {
        $this->enableHttp = true;
    }
}