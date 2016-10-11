<?php
/**
 * Abstract.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Juhe\Src;

use TheFairLib\Http\Curl;
use TheFairLib\Juhe\JuheException;
use TheFairLib\Utility\Utility;

abstract class API
{
    static public $instance;
    /**
     * @return API
     */
    static public function Instance()
    {
        $class = get_called_class();
        if (empty(self::$instance[$class])) {
            self::$instance[$class] = new $class();
        }
        return self::$instance[$class];
    }

    abstract protected function _getAppKey();

    /**
     * @param $url
     * @param $params
     * @param bool $returnResultOnly
     * @param string $responseFormat json|xml
     * @return array|mixed|string
     * @throws JuheException
     */
    protected function _sendRequest($url, $params, $returnResultOnly = true, $responseFormat = 'json'){
        $params = array_merge($params, [
            'key' => $this->_getAppKey(),
            'dtype' => $responseFormat,
        ]);
        $curl = new Curl();
        $curl->get($url, $params);
        $result = [];
        if (!empty($curl->response)) {
            $result = Utility::decode($curl->response);
            if($returnResultOnly === true && isset($result['result'])){
                if(!empty($result['error_code'])){
                    throw new JuheException("{$result['error_code']} : {$result['reason']}");
                }else{
                    $result = $result['result'];
                }
            }
        }

        return $result;
    }
}