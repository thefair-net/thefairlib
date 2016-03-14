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
        $result = $this->send($this->_encode(['url' => $url, 'params' => $params]), $callback);
        return $this->_decode($result);
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
