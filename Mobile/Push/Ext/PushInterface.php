<?php
/**
 * Interface.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Mobile\Push\Ext;

interface PushInterface
{
    public function sendPushToSingleDevice($deviceToken, $platform, $message);

    public function sendPushToDeviceList($deviceToken, $platform, $message);
}