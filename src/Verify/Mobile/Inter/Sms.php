<?php
/**
 * Created by PhpStorm.
 * User: liumingzhi
 * Date: 15/10/31
 * Time: 下午11:46
 */

namespace TheFairLib\Verify\Mobile\Inter;

interface Sms
{
    //发送单条信息
    public function sendMessage($mobile, $msg);

    //群发信息
    public function sendMessageList($mobileAndMsgList);

    //模板信息
    public function sendTplMessage($tpl, $mobile, $msg);

    //模板信息
    public function sendTplMessageList($tpl, $mobileList, $msg);

    //发送验证码
    public function sendVerifyCode($mobile, $codeMsg, $extParam);
}