<?php
/**
 * RpcClient.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Service\Taoo\Rpc;
use TheFairLib\Service\Swoole\Client\TCP;

class RpcClient extends TCP
{
    public function call($url, $params, callable $callback = NULL){
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
        //@todo 异常处理

        return $result['result'];
    }

    protected function _getClientType(){
        return 'rpc';
    }

    protected function _encode($data){
        return json_encode($data);
    }

    protected function _decode($data){
        return json_decode($data, true);
    }
}
