<?php
/**
 * RpcClient.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Service\Taoo\Rpc;
use TheFairLib\Config\Config;
use TheFairLib\DB\Redis\Cache;
use TheFairLib\Exception\BaseException;
use TheFairLib\Logger\Logger;
use TheFairLib\Service\Swoole\Client\TCP;
use TheFairLib\Utility\Utility;
use Yaf\Exception;

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

    /**
     * 智能获取数据
     *
     * @param $url
     * @param array $params
     * @param bool $showResultOnly
     * @return bool|mixed|string
     * @throws Exception
     * @throws \TheFairLib\Service\Exception
     */
    public function smart($url, $params = [], $showResultOnly = true){
        $result = null;
        $cacheTtl = $this->_getServiceCacheTtl($url);
        //获取缓存的key
        $cacheKey = $this->_getServiceCacheKey($url, $params);
        if($cacheTtl !== false){
            $result = $this->_getCache()->get($cacheKey);
        }

        if(empty($result)){
            $result = $this->call($url, $params);
            if($cacheTtl !== false){
                $this->_getCache()->setex($cacheKey, $cacheTtl, Utility::encode($result));
            }
        }else{
            $result = Utility::decode($result);
        }

        //如果设置了只返回结果,当code!=0的时候,直接抛出异常
        if($showResultOnly === true){
            if(!empty($result['code'])){
                throw new \TheFairLib\Service\Exception($result['message'], $result['code']);
            }else{
                return $result['result'];
            }
        }else{
            return $result;
        }
    }

    protected function _getClientType(){
        return 'rpc';
    }

    protected function _encode($data){
        $data = base64_encode(gzcompress(Utility::encode($data)));
        //因为swoole扩展启用了open_length_check,需要在数据头部增加header @todo 增加长度校验及扩展头
        return pack("N", strlen($data)) .$data;
    }

    protected function _decode($data){
        $data = substr($data, 4);
        return Utility::decode(gzuncompress(base64_decode($data)));
    }

    private function _getServiceCacheTtl($url){
        $key = $this->_getClientType().'.'.$this->getServerTag().'.'.str_replace('/', '_', substr($url, 1));
        $ttl = Config::get_service_cache($key);
        return empty($ttl) ? false : $ttl;
    }

    private function _getServiceCacheKey($url, $params){
        $serviceConfig = $this->_getServiceConfigKey($url);
        return !empty($serviceConfig) ? 'service_cache_'.Config::get_app('phase').'::'.$serviceConfig.'_'.md5($this->getServerTag().$url.Utility::encode($params)) : null;
    }

    private function _getServiceConfigKey($url){
        return strtolower($this->getServerTag().'::'.str_replace('/', '_', substr($url, 1)));
    }

    private function _getCache(){
        $serviceConf = $this->_getServiceConfig($this->getServerTag());
        $cacheNode = !empty($serviceConf['cache_node']) ? $serviceConf['cache_node'] : 'default';
        return Cache::getInstance($cacheNode);
    }
}
