<?php
/**
 * DingxiangClient.php
 *
 * @author wangjianqiu <wangjianqiu@thefair.net.cn>
 * @version 1.0
 * @copyright 2018-2018 TheFair
 */

namespace TheFairLib\Dingxiang;

include __DIR__ . "/Sdk/DeviceFingerprintHandle.php";
include __DIR__ . "/Sdk/ServicesRegion.php";

class DingxingClient
{
    const CONN_TIME_OUT = 5;

    protected $appId = '';
    protected $appSecret = '';

    protected $client;

    public function __construct($appId, $appSecret, $timeOut = self::CONN_TIME_OUT)
    {
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->client = new \DeviceFingerprintHandle();
        $this->client->setTimeout($timeOut);
    }

    public function getDeviceDetail($token)
    {
        $response = $this->client->getDeviceInfo(\ServicesRegion::EAST_ASIA, $this->appId, $this->appSecret, $token);
        return $response;
    }
}