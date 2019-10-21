<?php
/**
 * Created by PhpStorm.
 * User: wuling
 * Date: 2018/5/17
 * Time: 下午7:30
 */

class DeviceFingerprintHandle {

    // 默认单位秒
    public $timeout = 2;

    /**
     * @param $timeout  单位秒
     */
    public function setTimeout($timeout) {
        $this->timeout = $timeout;
    }

    public function getTimeout() {
        return $this->timeout;
    }

    /**
     * 根据token获取设备详细信息
     *
     * @param $url          设备指纹服务器url
     * @param $appId        AppID
     * @param $appSecret    AppSecret
     * @param $token        token
     * @return              json格式的返回值
     */
    public function getDeviceInfo($url, $appId, $appSecret, $token) {
        $requestUrl = $url. "?appId=". $appId. "&token=". rawurlencode($token). "&sign=". md5($appSecret. $token. $appSecret);
        return $this->doGetRequest($requestUrl);
    }

    /**
     * 提交GET请求
     *
     * @param $url          请求url
     * @return bool|string  本次请求结果集
     * @throws Exception    网络不通或网络超时将抛出异常
     */
    public function doGetRequest($url) {
        $params = array('http' => array(
            'method' => 'GET',
            'header' => 'Content-type:text/html',
            'timeout' => $this->timeout
        ));

        $ctx = stream_context_create($params);
        $fp = @fopen($url, 'rb', false, $ctx);
        if (!$fp) {
            throw new Exception("链接超时或失败 $url, $php_errormsg");
        }
        $response = @stream_get_contents($fp);
        if ($response === false) {
            throw new Exception("读取数据失败 $url, $php_errormsg");
        }

        return $response;
    }
}