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
            if(defined('APP_NAME')){
                $path = '/home/thefair/logs/www/'.APP_NAME.'/';
                if( !is_dir($path) ) {
                    mkdir($path, 0777, true);
                }
                $log = $path.date('Y-m-d').'.log';
                $s = date("Y-m-d H:i:s +u")." ";
                file_put_contents($log, $s."来源IP:{$_SERVER['REMOTE_ADDR']}\n", FILE_APPEND|LOCK_EX);
                file_put_contents($log, $s."请求接口:{$_SERVER['REQUEST_URI']}\n", FILE_APPEND|LOCK_EX);
                file_put_contents($log, $s."请求Cookie:".json_encode($_COOKIE)."\n", FILE_APPEND|LOCK_EX);
                file_put_contents($log, $s."请求参数:".json_encode($_REQUEST)."\n", FILE_APPEND|LOCK_EX);
                file_put_contents($log, $s."错误信息:".$e->getMessage()."\n", FILE_APPEND|LOCK_EX);
                file_put_contents($log, $s."Trace:".$e->getTraceAsString()."\n\n", FILE_APPEND|LOCK_EX);
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
        $this->showError('Illegal Request: '.$msg, [], 40000, 500);
    }

    protected function _DealNotfoundRequest(){
        $this->showError('Not Found', [], 40000, 404);
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