<?php
/**
 * Some Info
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */

namespace TheFairLib\Exception\Service;

use TheFairLib\Config\Config;
use TheFairLib\Exception\BaseException;

class ServiceException extends BaseException
{
    private $extData = array();
    private $extCode = '10000';
    private $originalCode = '';

    public function __construct($msg, $data = array(), $code = '40001'){
        //检查msg，如果是int，check下error配置，是否存在该错误码
        if(is_int($msg)){
            $errorMsg = Config::get_error($msg);
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

        parent::__construct((string)$msg);
        $this->extData      = $data;
        $this->extCode      = $code;
    }

    public function getExtCode(){
        return $this->extCode;
    }

    public function getExtData(){
        return $this->extData;
    }

    public function getOriginalCode(){
        return $this->originalCode;
    }
}