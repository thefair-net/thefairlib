<?php
/**
 * Created by PhpStorm.
 * User: liumingzhi
 * Date: 15/10/31
 * Time: 下午11:46
 */

namespace TheFairLib\Verify\Mobile\Inter;

interface Mobile
{
    //发送单条信息
    public function sendMessage($mobile, $msg);

    //群发信息
    public function sendMessageList($mobile, $msg);
}