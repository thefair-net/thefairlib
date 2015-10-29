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

class Api extends Response
{
    private $_result = array();

    private $_msg = '';

    private $_code = 0;

    private $_jsonpCallbackName;
    private $_jsonPrefix = '';

    public function __construct(array $result, $msg = '', $code = 0, $httpCode = 200){
        $this->setResult($result);
        $this->setMsg($msg);
        $this->setCode($code);

        parent::__construct(array(
            'code' => $this->getCode(),
            'message' => $this->getMsg(),
            'result' => $this->getResult(),
        ), $httpCode);
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

    protected function _serialize($content){
        $content = json_encode($content);

        if (!empty($this->_jsonpCallbackName)){
            $content = $this->_jsonpCallbackName.'('.$content.')';
        }elseif (!empty($this->_jsonPrefix)){
            $content = $this->_jsonPrefix.$content;
        }
        return $content;
    }

    protected function _getContentType(){
        return 'application/json;charset=utf-8';
    }
}