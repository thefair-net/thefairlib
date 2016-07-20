<?php
/**
 * Thefair Page Exception
 * 页面相关异常处理
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Exception\Page;

use TheFairLib\Exception\BaseException;
use TheFairLib\Config\Config;
use TheFairLib\I18N\TranslateHelper;

class PageException extends BaseException
{
    private $extData = array();
    private $extCode = '10000';
    private $originalCode = '';
    private $httpStatus = 400;

    public function __construct($msg, $data = array(), $code = '40001', $httpStatus = 400){
        //检查msg，如果是int，check下error配置，是否存在该错误码
        if(is_int($msg)){
            $errorMsg = Config::get_error($msg);
            if(!empty($errorMsg)){
                $this->originalCode = $msg;
                if(is_array($errorMsg)){
                    $code   = !empty($errorMsg['code']) ? $errorMsg['code'] : $msg;
                    $langM  = TranslateHelper::translate('api_error', $errorMsg['lang']);
                    $msg    = !empty($langM) ? $langM : (!empty($errorMsg['msg']) ? $errorMsg['msg'] : '');
                }else{
                    $code   = $msg;
                    $msg    = $errorMsg;
                }
            }
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