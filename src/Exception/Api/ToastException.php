<?php
/**
 * Thefair Toast Exception
 * API相关异常处理
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Exception\Api;

use TheFairLib\Http\Response\Api;

class ToastException extends ApiException
{
    public function __construct($msg, $data = array(), $code = '40001', $httpStatus = 400){
        parent::__construct($msg, $data, $code, $httpStatus, Api::ACTION_TOAST);
    }
}