<?php
/**
 * Jpush.php
 *
 * @author liumingzhi
 * @version 1.0
 * @copyright 2015-2015
 * @date 16/6/3 下午4:06
 */
namespace TheFairLib\Mobile\Jpush;

use TheFairLib\Config\Config;
use TheFairLib\Mobile\Push\Ext\PushInterface;
use Yaf\Exception;

require './src/JPush/JPush.php';


class Jpush implements PushInterface
{

    private $_appKey = null;
    private $_masterSecret = null;

    private $_push = null;

    public function __construct()
    {
        //获取个推配置
        $config = Config::get_notification_push_jpush('system_conf');
        if (empty($config) || empty($config['app_key']) || empty($config['master_secret'])) {
            throw new Exception('getui conf error');
        }
        $this->_appKey = $config['app_key'];
        $this->_masterSecret = $config['master_secret'];
        if (!empty($config['api_url'])) {
            $this->_httpHost = $config['api_url'];
        }
        $this->_push = new \Jpush($this->_appKey, $this->_masterSecret);
        return $this;
    }

    public function sendPushToSingleDevice($deviceToken, $platform, $title, $message, $link, $badge)
    {

    }

    public function sendPushToDeviceList($deviceTokenList, $platform, $title, $message, $link, $badge)
    {

    }
}