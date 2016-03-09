<?php
namespace TheFairLib\Service\Swoole\Network;
use TheFairLib\Service\Swoole\Server;
use TheFairLib\Service\Swoole\Server\Driver;

/**
 * Class Server
 * @package Swoole\Network
 */
class TcpServer extends Server implements Driver
{
    protected $sockType = SWOOLE_SOCK_TCP;

    public $setting = array(
        //      'open_cpu_affinity' => 1,
        'open_tcp_nodelay' => 1,
    );
}
