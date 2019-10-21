<?php
/**
 * Abstract.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Baidu\Map;
abstract class Base{
    private static $instance = null;
    protected $_appKey = null;
    protected $_outPut = 'json';

    static public function Instance(){
        if (empty(self::$instance)) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    final public function __construct()
    {
        $this->_appKey = \TheFairLib\Config\Config::get_api_baidu('app_key');
    }

    abstract protected function _getApiUrl();

    protected function _sendRequest($params){
        $url = $this->_getApiUrl();
        $params = array_merge($params, [
            'ak' => $this->_appKey,
            'output' => $this->_outPut,
        ]);
        $curl = new \TheFairLib\Http\Curl();
        $curl->get($url, $params);
        $result = json_decode($curl->response, true);
        if (!empty($result)) {
            $ret = $result['result'];
        }else{
            $ret = false;
        }
        return $ret;
    }
}