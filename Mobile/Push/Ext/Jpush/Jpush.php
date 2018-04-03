<?php
/**
 * Jpush.php
 *
 * @author liumingzhi
 * @version 1.0
 * @copyright 2015-2015
 * @date 16/6/3 下午4:06
 */

namespace TheFairLib\Mobile\Push\Ext\Jpush;

use TheFairLib\Config\Config;
use TheFairLib\Mobile\Push\Ext\PushInterface;
use Yaf\Exception;

require dirname(__FILE__) . '/src/JPush/JPush.php';


class Jpush implements PushInterface
{

    private $_appKey = null;
    private $_masterSecret = null;
    private $_apnsProduction = false;//生成环境为true
    private $_push = null;

    public function __construct($configLabel = 'system_conf')
    {
        //获取个推配置
        $config = Config::get_notification_push_jpush($configLabel);
        $phase = Config::get_app('phase');
        if (empty($config) || empty($config['app_key']) || empty($config['master_secret']) || empty($phase)) {
            throw new Exception('getui conf error');
        }
        if ($phase == 'prod') {
            $this->_apnsProduction = true;
        }
        $this->_appKey = $config['app_key'];
        $this->_masterSecret = $config['master_secret'];
        if (!empty($config['api_url'])) {
            $this->_httpHost = $config['api_url'];
        }
        $this->_push = new \Jpush($this->_appKey, $this->_masterSecret, null);
        return $this;
    }

    public function sendPushToSingleDevice($deviceToken, $platform, $title, $message, $link, $badge)
    {

    }

    public function sendPushToDeviceList($deviceTokenList, $platform, $title, $message, $link, $badge)
    {

    }

    /**
     * 发送消息
     *
     * @param $clientId   可以为数组
     * @param $platform
     * @param $title
     * @param $message
     * @param $link
     * @param $badge
     * @param $builderId
     * @return array|null|object
     * @throws Exception
     */
    public function pushMessageToSingle($clientId, $platform, $title, $message, $link, $badge, $builderId = 1)
    {
        if ($platform == 'iphone') $platform = 'ios';
        if (empty($clientId) || !in_array($platform, ['ios', 'android', 'winphone'])
            || empty($title) || empty($message)
        ) {
            throw new Exception('error push param' . json_encode([$clientId, $platform, $title, $message, $link, $badge], JSON_UNESCAPED_UNICODE));
        }
        $result = null;
        switch ($platform) {
            case 'ios' :
                $result = $this->_push->push()->setPlatform($platform)
                    ->addRegistrationId($clientId)
                    ->setNotificationAlert($message)
                    ->addIosNotification($message, '', $badge, true, '', [
                        'p' => $link,
                    ])
                    ->setOptions(86400, 3600, null, $this->_apnsProduction)
                    ->send();
                break;
            case 'android' :
                $result = $this->_push->push()->setPlatform($platform)
                    ->addRegistrationId($clientId)
                    ->setNotificationAlert($message)
                    ->addAndroidNotification($message, $title, $builderId, [
                        'p' => $link,
                    ])
                    ->setOptions(86400, 3600, null, $this->_apnsProduction)
                    ->send();
                break;
            default:
                throw new Exception('error platform' . $platform);
        }
        return $result;
    }

    public function pushMessageToAll($message)
    {
        $result = $this->_push->push()->setPlatform('all')
            ->addAllAudience()
            ->setNotificationAlert($message)
            ->setOptions(86400, 3600, null, $this->_apnsProduction)
            ->send();
        return $result;
    }

    public function device()
    {
        return $this->_push->device();
    }

    public function report()
    {
        return $this->_push->report();
    }

    public function push()
    {
        return $this->_push->push();
    }

}