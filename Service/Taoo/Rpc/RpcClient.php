<?php
/**
 * RpcClient.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Service\Taoo\Rpc;
use TheFairLib\Logger\Logger;
use TheFairLib\Service\Swoole\Client\TCP;

class RpcClient extends TCP
{
    public function call($url, $params = [], callable $callback = NULL){
        $requestData = [
            'auth' => [
                'app_key' => $this->_config['app_key'],
                'app_secret' => $this->_config['app_secret'],
            ],
            'request_data' => [
                'url' => $url,
                'params' => $params,
            ],
        ];
        $result = $this->send($this->_encode($requestData), $callback);
        $result = $this->_decode($result);

        if(!empty($result['code']) && $result['code'] >= 40000){
            Logger::Instance()->error($result['code'] .':'. $result['message']);
        }
        return $result;
    }

    protected function _getClientType(){
        return 'rpc';
    }

    protected function _encode($data){
        $data = base64_encode(gzcompress(json_encode($data, JSON_UNESCAPED_UNICODE)));
        //因为swoole扩展启用了open_length_check,需要在数据头部增加header @todo 增加长度校验及扩展头
        return pack("N", strlen($data)) .$data;
    }

    protected function _decode($data){
        $data = substr($data, 4);
        return json_decode(gzuncompress(base64_decode($data)), true);
    }
}
