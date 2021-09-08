<?php
/**
 * Controller.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */

namespace TheFairLib\Controller\Api;

use TheFairLib\Controller\Base;
use TheFairLib\Http\Response\Api;

class Controller extends Base
{
    /**
     * @var Api
     */
    protected static $_responseObj = false;

    public function init()
    {
        if (self::$_responseObj === false) {
            self::$_responseObj = new Api(new \stdClass());
        }
    }

    public function showResult($result, $msg = '', $code = '0', $action = Api::ACTION_LOG)
    {
        self::$_responseObj->setCode($code);
        self::$_responseObj->setMsg($msg);
        self::$_responseObj->setResult($result);
        $this->_setResponse(self::$_responseObj->send());
    }

    public function showError($error, $result = [], $code = '40001', $action = Api::ACTION_LOG)
    {
        $this->showResult($result, $error, $code);
    }

    public function showSuccess($msg = '')
    {
        $this->showResult(['state' => true], (!empty($msg) ? $msg : 'success'));
    }

    public function redirect($url, array $result = [])
    {
        self::$_responseObj->setAction(self::$_responseObj::ACTION_REDIRECT);
        $this->showResult($result, $url);
    }
}