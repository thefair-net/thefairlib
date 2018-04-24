<?php
/**
 * Error.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Controller\Service;

use TheFairLib\Controller\ErrorBase;
use TheFairLib\Exception\Service\ServiceException;
use TheFairLib\Http\Response\Service;
use TheFairLib\Logger\Logger;
use TheFairLib\Utility\Utility;
use \Yaf\Exception as Exception;

class Error extends ErrorBase
{
    /**
     * @var Service
     */
    protected static $_responseObj = false;
    protected function init(){
        if(self::$_responseObj === false){
            self::$_responseObj = new Service(new \stdClass());
        }
    }

    protected function _errorDefault(\Exception $e){
        if($e instanceof ServiceException){
            $this->showError(
                $e->getMessage(), $e->getExtData(),  $e->getExtCode()
            );
        }else{
//            if(defined('APP_NAME')){
//                Logger::Instance()->error(  date("Y-m-d H:i:s +u")."\n"
//                                            ."请求接口:{$_SERVER['REQUEST_URI']}\n"
//                                            ."请求参数:".Utility::encode($_REQUEST)."\n"
//                                            ."错误信息:".$e->getMessage()."\n"
//                                            ."Trace:".$e->getTraceAsString()."\n\n");
//            }
            $this->_DealIllegalRequest($e->getMessage(),['trace' => $e->getTraceAsString()]);
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

    protected function _DealIllegalRequest($msg = '',$data = []){
        $this->showError('Illegal Request: '.$msg, $data, 40001);
    }

    protected function _DealNotfoundRequest(){
        $this->showError('Not Found', [], 40001);
    }

    public function showResult($result, $msg = '', $code = '0'){
        self::$_responseObj->setCode($code);
        self::$_responseObj->setMsg($msg);
        self::$_responseObj->setResult($result);
        $this->_setResponse(self::$_responseObj->send());
    }

    public function showError($error, $result = array() , $code = '40001'){
        $this->showResult($result, $error, $code);
    }
}