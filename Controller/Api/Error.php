<?php
/**
 * Error.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Controller\Api;

use TheFairLib\Controller\ErrorBase;
use TheFairLib\Exception\Api\ApiException;
use \Yaf\Exception as Exception;
use TheFairLib\Http\Response\Api;

class Error extends ErrorBase
{
    protected static $_responseObj = false;
    protected function init(){
        if(self::$_responseObj === false){
            self::$_responseObj = new Api(new \stdClass());
        }
    }

    protected function _errorDefault(\Exception $e){
        if($e instanceof ApiException){
            $this->showError(
                $e->getMessage(), $e->getExtData(),  $e->getExtCode(), $e->getHttpStatus()
            );
        }else{
            $this->_DealIllegalRequest($e->getMessage());
        }

    }

    protected function _errorNotfoundModule(Exception $e){
        $this->_DealNotfoundRequest();
    }

    protected function _errorNotfoundController(Exception $e){
        $this->_DealNotfoundRequest();
    }

    protected function _errorNotfoundAction(Exception $e){
        $this->_DealNotfoundRequest();
    }

    protected function _errorNotfoundView(Exception $e){
        $this->_DealNotfoundRequest();
    }

    protected function _DealIllegalRequest($msg = ''){
        $this->showError(
            new Api(array(), 'Illegal Request: '.$msg, 40000, 404)
        );
    }

    protected function _DealNotfoundRequest(){
        $this->showError(
            new Api(array(), 'Illegal Request', 40000, 404)
        );
    }

    public function showResult($result, $msg = '', $code = '0'){
        self::$_responseObj->setCode($code);
        self::$_responseObj->setMsg($msg);
        if(!empty($result)){
            self::$_responseObj->setResult($result);

        }
        $this->_setResponse(self::$_responseObj->send());
    }

    public function showError($error, $result = array() , $code = '10000', $httpCode = 400){
        self::$_responseObj->setHttpCode($httpCode);
        $this->showResult($result, $error, $code);
    }
}