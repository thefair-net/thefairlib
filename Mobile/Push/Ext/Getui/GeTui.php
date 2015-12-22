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

    public function __construct()
    {
        //获取个推配置
        $config = Config::get_notification_push_getui('system_conf');
        if (empty($config) || empty($config['app_id']) || empty($config['app_secret']) || empty($config['app_key']) || empty($config['master_secret'])) {
            throw new Exception('getui conf error');
        }
        $this->_appID = $config['app_id'];
        $this->_appSecret = $config['app_secret'];
        $this->_appKey = $config['app_key'];
        $this->_masterSecret = $config['master_secret'];
        if(!empty($config['api_url'])){
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
}