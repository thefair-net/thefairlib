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

    private $_action = '';

    const ACTION_LOG = 'log';
    const ACTION_TOAST = 'toast';
    const ACTION_REDIRECT = 'redirect';

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

    public function getAction(){
        return $this->_action;
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

    public function setAction($action){
        return $this->_action = $action;
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

    public function send($dealHeader = true){
        $cookies = Utility::getResponseCookie();
        if(!empty($cookies)){
            foreach($cookies as $cookie){
                $this->setCookie($cookie);
            }
        }
        $this->setBody($this->_buildApiBody());
        return parent::send($dealHeader);
    }

    private function _buildApiBody(){
        $action = $this->getAction();
        $msg = $this->getMsg();
        return array(
            'code' => $this->getCode(),
            'message' => !empty($action) ? array('content' => $msg, 'action' => $action) : (!empty($msg) ? array('content' => $msg, 'action' => self::ACTION_LOG) : new \stdClass()),
            'result' => (object) $this->getResult(),
        );
    }
}