<?php
namespace TheFairLib\Service\Swoole\Client;

class Base
{

    public $ip;
    public $port;
    public $data;
    public $timeout = 5;

    public function __construct($ip, $port, $data, $timeout)
    {
        $this->ip = $ip;
        $this->port = $port;
        $this->data = $data;
        $this->timeout = $timeout;
    }

    public function send(callable $callback)
    {


    }
}