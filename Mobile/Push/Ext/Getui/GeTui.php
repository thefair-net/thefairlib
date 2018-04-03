<?php
/**
 * GeTui.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */

namespace TheFairLib\Mobile\Push\Ext\Getui;

use TheFairLib\Config\Config;
use TheFairLib\Mobile\Push\Ext\PushInterface;
use TheFairLib\Utility\Utility;
use Yaf\Exception;

require_once(dirname(__FILE__) . '/' . 'IGt.Push.php');
require_once(dirname(__FILE__) . '/' . 'igetui/IGt.AppMessage.php');
require_once(dirname(__FILE__) . '/' . 'igetui/IGt.APNPayload.php');
require_once(dirname(__FILE__) . '/' . 'igetui/template/IGt.BaseTemplate.php');
require_once(dirname(__FILE__) . '/' . 'IGt.Batch.php');
require_once(dirname(__FILE__) . '/' . 'igetui/utils/AppConditions.php');

class GeTui implements PushInterface
{
    //http的域名
//    private $_httpHost = 'http://sdk.open.api.igexin.com/apiex.htm';
    private $_httpHost = 'https://api.getui.com/apiex.htm';

    private $_appID = null;
    private $_appSecret = null;
    private $_appKey = null;
    private $_masterSecret = null;

    private $_iGeTui = null;

    public function __construct($configLabel = 'system_conf')
    {
        //获取个推配置
        $config = Config::get_notification_push_getui($configLabel);
        if (empty($config) || empty($config['app_id']) || empty($config['app_secret']) || empty($config['app_key']) || empty($config['master_secret'])) {
            throw new Exception('getui conf error');
        }
        $this->_appID = $config['app_id'];
        $this->_appSecret = $config['app_secret'];
        $this->_appKey = $config['app_key'];
        $this->_masterSecret = $config['master_secret'];
        if (!empty($config['api_url'])) {
            $this->_httpHost = $config['api_url'];
        }

        $this->_iGeTui = new \IGeTui($this->_httpHost, $this->_appKey, $this->_masterSecret, false);
        return $this;
    }

    public function sendPushToSingleDevice($deviceToken, $platform, $title, $message, $link, $badge)
    {
        $template = new \IGtTransmissionTemplate();
        $template->set_appId($this->_appID);//应用appid
        $template->set_appkey($this->_appKey);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent($message);//透传内容

        //APN高级推送
        $apn = new \IGtAPNPayload();
        $alertmsg = new \DictionaryAlertMsg();
        $alertmsg->body = $message;
        $alertmsg->title = $title;

        $apn->alertMsg = $alertmsg;
        $apn->badge = $badge;
        $apn->sound = "";
        $apn->customMsg = ['p' => $link];
        $template->set_apnInfo($apn);

        $messageObj = new \IGtSingleMessage();
        $messageObj->set_data($template);
        $ret = $this->_iGeTui->pushAPNMessageToSingle($this->_appID, $deviceToken, $messageObj);

        return $ret;
    }

    public function sendPushToDeviceList($deviceTokenList, $platform, $title, $message, $link, $badge)
    {
        $template = new \IGtTransmissionTemplate();
        $template->set_appId($this->_appID);//应用appid
        $template->set_appkey($this->_appKey);//应用appkey
        $template->set_transmissionType(1);//透传消息类型
        $template->set_transmissionContent($message);//透传内容

        //APN高级推送
        $apn = new \IGtAPNPayload();
        $alertmsg = new \DictionaryAlertMsg();
        $alertmsg->body = $message;
        $alertmsg->title = $title;

        $apn->alertMsg = $alertmsg;
        $apn->badge = $badge;
        $apn->sound = "";
        $apn->customMsg = ['p' => $link];
        $template->set_apnInfo($apn);

        putenv("needDetails=true");
        $messageObj = new \IGtListMessage();
        $messageObj->set_data($template);
        $contentId = $this->_iGeTui->getAPNContentId($this->_appID, $messageObj);

        $ret = $this->_iGeTui->pushAPNMessageToList($this->_appID, $contentId, $deviceTokenList);

        return $ret;
    }

    /**
     * 设置用户设备标签
     *
     * @param $clientId
     * @param array $tags
     * @return bool|mixed|null
     */
    public function setDeviceTags($clientId, array $tags)
    {
        $ret = false;
        if (!empty($tags)) {
            $ret = $this->_iGeTui->setClientTag($this->_appID, $clientId, $tags);
        }

        return $ret;
    }

    /**
     * 获取用户设备标签
     *
     * @param $clientId
     * @return array
     */
    public function getDeviceTags($clientId)
    {
        return $this->_iGeTui->getUserTags($this->_appID, $clientId);
    }

    /**
     * 单推接口案例
     *
     * @param $clientId
     * @param $tempType
     * @param $platform
     * @param $title
     * @param $message
     * @param $link
     * @param $badge
     * @param string $logoUrl
     * @return array
     * @throws Exception
     * @throws \Exception
     */
    public function pushMessageToSingle($clientId, $tempType, $platform, $title, $message, $link, $badge, $logoUrl = '')
    {
        if (empty($clientId) || !in_array($tempType, ['Transmission', 'Notification', 'Link']) || !in_array($platform, ['iphone', 'android', 'ios'])
            || empty($title) || empty($message)
        ) {
            throw new Exception('error push param' . json_encode([$clientId, $tempType, $platform, $title, $message, $link, $badge, $logoUrl], JSON_UNESCAPED_UNICODE));
        }
        //APN高级推送
        $apn = new \IGtAPNPayload();
        $alertMsg = new \DictionaryAlertMsg();
        $alertMsg->body = $message;
//        IOS8.2 支持
        $alertMsg->title = $title;
        $alertMsg->titleLocKey = $title;

        $apn->alertMsg = $alertMsg;
        $apn->badge = $badge;
        $apn->add_customMsg("payload", "payload");
        $apn->contentAvailable = 1;
        $apn->category = "ACTIONABLE";
        $apn->customMsg = ['p' => $link];
        $msg = ['p' => $link, 'title' => $title, 'content' => $message];
        $msg = Utility::encode($msg);
        if (in_array($platform, ['iphone', 'ios'])) {
            $tempType = 'Transmission';
            $template = $this->_setTemplate($tempType, $title, $message, $link, $logoUrl, 'logo.png', 1, $msg);
            $template->set_pushInfo("", $badge, $message, "", "payload", "", "", "");
        } else {
            $template = $this->_setTemplate($tempType, $title, $message, $link, $logoUrl, 'logo.png', 2, $msg);
        }
        $template->set_apnInfo($apn);
        //个推信息体
        $message = new \IGtSingleMessage();

        $message->set_isOffline(true);//是否离线
        $message->set_offlineExpireTime(3600 * 12 * 1000);//离线时间
        $message->set_data($template);//设置推送消息类型
        //$message->set_PushNetWorkType(0);//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
        //接收方
        $target = new \IGtTarget();
        $target->set_appId($this->_appID);
        $target->set_clientId($clientId);
        //$target->set_alias(Alias);

        try {
            $result = $this->_iGeTui->pushMessageToSingle($message, $target);
            return $result;
        } catch (\RequestException $e) {
//            $requestId = $e->getRequestId();
//            $result = $this->_iGeTui->pushMessageToSingle($message, $target, $requestId);
//            return $result;
            return $e;
        }
    }

    /**
     * 多推接口案例
     *
     * @param $clientList
     * @param $tempType
     * @param $platform
     * @param $title
     * @param $message
     * @param $link
     * @param int $badge
     * @param string $logoUrl
     * @return array
     * @throws Exception
     * @throws \Exception
     */
    public function pushMessageToList($clientList, $tempType, $platform, $title, $message, $link, $badge = 1, $logoUrl = '')
    {
        if (empty($clientList) || !in_array($tempType, ['Transmission', 'Notification', 'Link']) || !in_array($platform, ['iphone', 'android', 'ios'])
            || empty($title) || strlen($title) >= 40 || empty($message)
        ) {
            throw new Exception('error push param' . json_encode([$tempType, $platform, $title, $message, $link, $badge, $logoUrl], JSON_UNESCAPED_UNICODE));
        }
        putenv("gexin_pushList_needDetails=true");
        putenv("gexin_pushList_needAsync=true");

        //APN高级推送
        $apn = new \IGtAPNPayload();
        $alertMsg = new \DictionaryAlertMsg();
        $alertMsg->body = $message;
//        IOS8.2 支持
        $alertMsg->title = $title;
        $alertMsg->titleLocKey = $title;
        $apn->badge = $badge;
        $apn->alertMsg = $alertMsg;
        $apn->add_customMsg("payload", "payload");
        $apn->contentAvailable = 1;
        $apn->category = "ACTIONABLE";
        $apn->customMsg = ['p' => $link];
        $msg = ['p' => $link, 'title' => $title, 'content' => $message];
        $msg = Utility::encode($msg);
        if (in_array($platform, ['iphone', 'ios'])) {
            $tempType = 'Transmission';
            $template = $this->_setTemplate($tempType, $title, $message, $link, $logoUrl, 'logo.png', 1, $msg);
            $template->set_pushInfo("", $badge, $message, "", "payload", "", "", "");
        } else {
            $template = $this->_setTemplate($tempType, $title, $message, $link, $logoUrl, 'logo.png', 1, $msg);
        }
        $template->set_apnInfo($apn);
        //个推信息体
        $message = new \IGtListMessage();
        $message->set_isOffline(true);//是否离线
        $message->set_offlineExpireTime(3600 * 12 * 1000);//离线时间
        $message->set_data($template);//设置推送消息类型
//    $message->set_PushNetWorkType(1);	//设置是否根据WIFI推送消息，1为wifi推送，0为不限制推送
//    $contentId = $igt->getContentId($message);
        $contentId = $this->_iGeTui->getContentId($message, date('YmdHis', time()) . '_' . $tempType);    //根据TaskId设置组名，支持下划线，中文，英文，数字
        $targetList = [];
        foreach ($clientList as $clientId) {
            //接收方1
            $target = new \IGtTarget();
            $target->set_appId($this->_appID);
            $target->set_clientId($clientId);
            //$target1->set_alias(Alias);
            $targetList[] = $target;
        }
        $result = $this->_iGeTui->pushMessageToList($contentId, $targetList);
        return $result;
    }

    private function _setTemplate($type, $title, $message, $link = '', $logoUrl = '', $logo = 'logo.png', $transmission = 1, $transmissionContent = '')
    {
        //消息模版：
        // 1.TransmissionTemplate:透传功能模板
        // 2.LinkTemplate:通知打开链接功能模板
        // 3.NotificationTemplate：通知透传功能模板
        // 4.NotyPopLoadTemplate：通知弹框下载功能模板
        $template = null;
        switch ($type) {
            case 'Notification' :
                $template = new \IGtNotificationTemplate();
                $template->set_appId($this->_appID);//应用appid
                $template->set_appkey($this->_appKey);//应用appkey
                $template->set_transmissionType($transmission);//透传消息类型
                $template->set_transmissionContent($transmissionContent);//透传内容
                $template->set_title($title);//通知栏标题
                $template->set_text($message);//通知栏内容
                $template->set_logo($logo);//通知的图标名称，包含后缀名（需要在客户端开发时嵌入），如“push.png”
                $template->set_isRing(true);//是否响铃
                $template->set_isVibrate(true);//是否震动
                $template->set_isClearable(true);//通知栏是否可清除
                $template->set_logoURL($logoUrl);
                break;
            case 'Link' :
                $template = new \IGtLinkTemplate();
                $template->set_appId($this->_appID);//应用appid
                $template->set_appkey($this->_appKey);//应用appkey
                $template->set_title($title);//通知栏标题
                $template->set_text($message);//通知栏内容
                $template->set_logo($logo);//通知栏logo
                $template->set_isRing(true);//是否响铃
                $template->set_isVibrate(true);//是否震动
                $template->set_isClearable(true);//通知栏是否可清除
                $template->set_url($link);//打开连接地址,不能超过200个字符
                break;
            case 'Transmission' :
                $template = new \IGtTransmissionTemplate();
                $template->set_appId($this->_appID);//应用appid
                $template->set_appkey($this->_appKey);//应用appkey
                $template->set_transmissionType($transmission);//透传消息类型
                $template->set_transmissionContent($transmissionContent);//透传内容
                break;
        }
        if (empty($template)) {
            throw new \Exception('error push Template' . $type);
        }
        return $template;
    }
}