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

    static $riskFields = array
    (
        'create_time', // 创建时间
        'log_type',    // 日志类型
        'token',       // 顶相token
        'device_id',   // 设备ID
        'platform',    // 手机平台
        'platform_v',  // 手机系统版本号
        'client_v',    // 客户端版本号
        'model',       // 手机型号
        'sign',        // 手机imei/idfv号
        'network',     // 网络环境
        'client_ip',   // 客户端IP地址
        'client_uri',  // 客户端URI
        'referer',     // 访问来源
        'server_ip',   // 服务端IP地址
        'mac',         // 手机MAC地址（顶相)
        'app_name',    // 项目名称
        'app_id',      // 产品标识
        'source',      // 渠道标识
        'sid',         // 会话ID
        'uid',         // 用户ID
        'openid',      // 微信账号
        'category',    // 行为分类 user.login
        'act_time',    // 行为发生时间
    );

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