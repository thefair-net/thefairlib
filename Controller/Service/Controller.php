<?php
/**
 * Controller.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Controller\Service;

use TheFairLib\Controller\Base;
use TheFairLib\Http\Response;
use TheFairLib\Http\Response\Service;

class Controller extends Base
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

    public function showResult($result, $msg = '', $code = '0'){
        self::$_responseObj->setCode($code);
        self::$_responseObj->setMsg($msg);
        if(!empty($result)){
            self::$_responseObj->setResult($result);

        }
        $this->_setResponse(self::$_responseObj->send());
    }

    public function showError($error, $result = array() , $code = '10000'){
        $this->showResult($result, $error, $code);
    }

}