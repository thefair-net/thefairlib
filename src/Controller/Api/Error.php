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
use TheFairLib\Logger\Logger;
use TheFairLib\Utility\Utility;
use \Yaf\Exception as Exception;
use TheFairLib\Http\Response\Api;

class Error extends ErrorBase
{
    /**
     * @var Api
     */
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
            if(defined('APP_NAME')){
                Logger::Instance()->error(  date("Y-m-d H:i:s +u")."\n"
                                            ."来源IP:{$_SERVER['REMOTE_ADDR']}\n"
                                            ."请求接口:{$_SERVER['REQUEST_URI']}\n"
                                            ."请求Cookie:".Utility::encode($_COOKIE)."\n"
                                            ."请求参数:".Utility::encode($_REQUEST)."\n"
                                            ."错误信息:".$e->getMessage()."\n"
                                            ."Trace:".$e->getTraceAsString()."\n\n");
            }
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
        $this->showError('Illegal Request: '.$msg, [], 40001, 500);
    }

    protected function _DealNotfoundRequest(){
        $this->showError('Not Found', [], 40001, 404);
    }

    public function showResult($result, $msg = '', $code = '0'){
        self::$_responseObj->setCode($code);
        self::$_responseObj->setMsg($msg);
        self::$_responseObj->setResult($result);
        $this->_setResponse(self::$_responseObj->send());
    }

    public function showError($error, $result = array() , $code = '40001', $httpCode = 400){
        self::$_responseObj->setHttpCode($httpCode);
        $this->showResult($result, $error, $code);
    }
}