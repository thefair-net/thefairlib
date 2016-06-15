<?php
/**
 * Api.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Http\Response;

use TheFairLib\Http\Response;
use TheFairLib\Utility\Utility;

class Api extends Response
{
    private $_result = array();

    private $_msg = '';

    private $_code = 0;

    private static $_jsonpCallbackName = 'callback';
    private static $_isJsonp = false;

    public function __construct($result, $msg = '', $code = 0, $httpCode = 200){
        $this->setResult($result);
        $this->setMsg($msg);
        $this->setCode($code);

        parent::__construct($this->_buildApiBody(), $httpCode);
    }

    public function getResult(){
        return $this->_result;
    }

    public function getMsg(){
        return $this->_msg;
    }

    public function getCode(){
        return $this->_code;
    }

    public function setResult($result){
        return $this->_result = $result;
    }

    public function setMsg($msg){
        return $this->_msg = $msg;
    }

    public function setCode($code){
        return $this->_code = $code;
    }

    public static function setCallBack($callback){
        return self::$_jsonpCallbackName = $callback;
    }

    public static function setIsJsonp($isJsonp){
        return self::$_isJsonp = $isJsonp;
    }

    protected function _serialize($content){
        $content = Utility::encode($content);

        if(self::$_isJsonp === true){
            $content = self::$_jsonpCallbackName . '(' . $content . ');';
        }

        return $content;
    }

    protected function _getContentType(){
        return 'application/json;charset=utf-8';
    }

    public function send(){
        $cookies = Utility::getResponseCookie();
        if(!empty($cookies)){
            foreach($cookies as $cookie){
                $this->setCookie($cookie);
            }
        }
        $this->setBody($this->_buildApiBody());
        return parent::send();
    }

    private function _buildApiBody(){
        return array(
            'code' => $this->getCode(),
            'message' => array('text' => $this->getMsg(), 'action' => 'toast'),
            'result' => (object) $this->getResult(),
        );
    }
}