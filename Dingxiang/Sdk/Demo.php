<?php
/**
 * Created by PhpStorm.
 * User: wuling
 * Date: 2018/5/17
 * Time: 下午10:19
 */

include "./DeviceFingerprintHandle.php";
include "./ServicesRegion.php";
// 时区
ini_set('date.timezone','Asia/Shanghai');

class Demo {
    // 根据实际情况填写
    const appSecret = "你的AppID";
    // 根据实际情况填写
    const appKey = "你的AppSecret";
    // 根据实际情况填写
    const token = "SDK里面获取到的token";
}

// 根据token获取设备详细信息工具类
$requestHandle = new DeviceFingerprintHandle();
// 设置默认超时时间，存在设备指纹降级和网络抖动的情况，默认2秒，可以根据实际情况调整。
// $requestHandle->setTimeout(2);

// 根据实际情况填写服务器所在区域
$responseData = $requestHandle->getDeviceInfo(ServicesRegion::EAST_ASIA, Demo::appKey, Demo::appSecret, Demo::token);
$result = json_decode($responseData, true);

// 请求状态码。非 200 表示没有获取到设备明细信息
if ($result['stateCode'] == 200)
    echo json_encode($result['data'], true);
else
    echo $result['message'];
