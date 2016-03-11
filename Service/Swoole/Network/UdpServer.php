<?php
namespace TheFairLib\Service\Swoole\Network;
/**
 * Class Server
 * @package Swoole\Network
 */
class UdpServer extends \TheFairLib\Service\Swoole\Server
{
    protected $sockType = SWOOLE_SOCK_UDP;

    public $setting = array( // udp server 默认配置

    );
}
