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
    public function call($url, $params, callable $callback = null){
        return $this->send(['url' => $url, 'params' => $params], $callback);
    }
}
