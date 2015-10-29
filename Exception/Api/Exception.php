<?php
/**
 * Thefair API Exception
 * API相关异常处理
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Exception\Api;

use TheFairLib\Config\Config;
use TheFairLib\Exception\Base;

class Exception extends Base
{
    private $extData = array();
    private $extCode = '10000';
    private $originalCode = '';
    private $httpStatus = 400;

    public function __construct($msg, $data = array(), $code = '40001', $httpStatus = 400){
        //检查msg，如果是int，check下error配置，是否存在该错误码
        if(is_int($msg)){
            $errorMsg = Config::load('error.'.$msg);
            if(!empty($errorMsg)){
                $this->originalCode = $msg;
                if(is_array($errorMsg)){
                    $code   = !empty($errorMsg['code']) ? $errorMsg['code'] : $msg;
                    $msg    = !empty($errorMsg['msg']) ? $errorMsg['msg'] : '';
                }else{
                    $code   = $msg;
                    $msg    = $errorMsg;
                }
            }
        }

        if($code < '40000' && $httpStatus == 400){
            $httpStatus = 405;
        }

        parent::__construct((string)$msg);
        $this->extData      = $data;
        $this->extCode      = $code;
        $this->httpStatus   = $httpStatus;
    }

    public function getExtCode(){
        return $this->extCode;
    }

    public function getExtData(){
        return $this->extData;
    }

    public function getHttpStatus(){
        return $this->httpStatus;
    }

    public function getOriginalCode(){
        return $this->originalCode;
    }
}