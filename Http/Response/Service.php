<?php
/**
 * Service.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Http\Response;

use TheFairLib\Http\Response;
use TheFairLib\Utility\Utility;

class Service extends Response
{
    private $_result = array();

    private $_msg = '';

    private $_code = 0;

    private $_callback = [];

    public function __construct($result, $msg = '', $code = 0, $callback = []){
        $this->setResult($result);
        $this->setMsg($msg);
        $this->setCode($code);
        $this->setCallback($callback);

        parent::__construct($this->_buildApiBody());
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

    public function getCallback(){
        return $this->_callback;
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

    public function setCallback($callback){
        return $this->_callback = $callback;
    }

    protected function _serialize($content){
        $content = Utility::encode($content);

        return $content;
    }

    protected function _getContentType(){
        return 'application/json;charset=utf-8';
    }

    protected function _getBodyToSend(){
        return $this->_serialize($this->getBody());
    }

    public function send(){
        $this->setBody($this->_buildApiBody());
        return parent::send(false);
    }

    private function _buildApiBody(){
        return array(
            'code' => $this->getCode(),
            'message' => $this->getMsg(),
            'result' => (object) $this->getResult(),
            'callback' => (object) $this->getCallback(),
        );
    }
}