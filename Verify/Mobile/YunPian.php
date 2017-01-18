<?php
/**
 * Created by PhpStorm.
 * User: liumingzhi
 * Date: 15/10/31
 * Time: 下午11:19
 */

namespace TheFairLib\Verify\Mobile;

use TheFairLib\Config\Config;
use TheFairLib\Http\Curl;
use TheFairLib\Utility\Utility;
use TheFairLib\Verify\Exception;
use TheFairLib\Verify\Mobile\Inter\Sms;

class YunPian implements Sms
{
    const SEND_URL = 'https://sms.yunpian.com/v2/sms/single_send.json';
    const SEND_URL_TPL = 'https://sms.yunpian.com/v2/sms/tpl_single_send.json';
    const BATCH_SEND_URL = 'https://sms.yunpian.com/v2/sms/multi_send.json';
    const BATCH_SEND_URL_TPL = 'https://sms.yunpian.com/v2/sms/tpl_batch_send.json';
    const VOICE_MSG_URL = 'https://voice.yunpian.com/v2/voice/send.json';

    private $_appKey;

    public function __construct($config = [])
    {
        if(empty($config)){
            $config = Config::get_verify();
        }

        if (!isset($config['appKey']) || empty($config['appKey']['YunPian']['key'])) {
            throw new Exception('common.appKey error');
        }
        $this->_appKey = $config['appKey']['YunPian']['key'];
    }

    protected function _sendPostRequest($url, $postData = []){
        $postData['apikey'] = $this->_appKey;
        $curl = new Curl();
        $curl->post($url, $postData);
        return Utility::decode($curl->response);
    }

    /**
     * 单条信息
     *
     * @param $mobile
     * @param $msg
     * @return null
     * @throws Exception
     */
    public function sendMessage($mobile, $msg)
    {
        $data = array(
            'mobile' => $mobile,
            'text' => $msg,
        );
        return $this->_sendPostRequest(self::SEND_URL, $data);
    }

    /**
     * 模板ID
     *
     * @param $tpl
     * @param $mobile
     * @param $msg //#code#=1234&#company#=桃花岛
     * @return null
     * @throws Exception
     */
    public function sendTplMessage($tpl, $mobile, $msg)
    {
        $data = array(
            'mobile' => $mobile,
            'tpl_id' => $tpl,
            'tpl_value' => $msg,
        );
        return $this->_sendPostRequest(self::SEND_URL_TPL, $data);
    }

    /**
     * 群发信息
     *
     * @param $mobileAndMsgList
     * @return null
     * @throws Exception
     */
    public function sendMessageList($mobileAndMsgList)
    {
        if(empty($mobileAndMsgList) || !is_array($mobileAndMsgList)){
            throw new Exception('mobileAndMsgList error');
        }
        if(count($mobileAndMsgList) > 1000){
            throw new Exception('mobileAndMsgList is too many');
        }
        $mobileList = $msgList = [];
        foreach($mobileAndMsgList as $item){
            $mobileList[] = $item['mobile'];
            $msgList[] = urlencode($item['msg']);
        }

        $data = array(
            'mobile' => implode(',', $mobileList),
            'text' => implode(',', $msgList),
        );
        return $this->_sendPostRequest(self::BATCH_SEND_URL, $data);
    }

    public function sendTplMessageList($tpl, $mobileList, $msg){
        if(empty($mobileList) || !is_array($mobileList)){
            throw new Exception('mobileAndMsgList error');
        }
        if(count($mobileList) > 1000){
            throw new Exception('mobileAndMsgList is too many');
        }
        $mobile = implode(',', $mobileList);
        $data = array(
            'mobile' => $mobile,
            'tpl_id' => $tpl,
            'tpl_value' => $msg,
        );
        return $this->_sendPostRequest(self::BATCH_SEND_URL_TPL, $data);
    }

    public function sendVoiceVerifyCode($mobile, $code){
        $data = array(
            'mobile' => $mobile,
            'code' => $code,
        );
        return $this->_sendPostRequest(self::VOICE_MSG_URL, $data);
    }

    public function sendVerifyCode($mobile, $code, $company){
        //触发语音短信的code
        $needSendVoiceSmsCodeList = ['10'];

        $result = $this->sendTplMessage(1, $mobile, '#code#='.$code.'&#company#='.$company);
        if(!empty($result['code']) && in_array($result['code'], $needSendVoiceSmsCodeList)){
            $this->sendVoiceVerifyCode($mobile, $code);
        }

        return $result;
    }
}