<?php
/**
 * Controller.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Controller\Page;

use TheFairLib\Controller\Base;
use TheFairLib\Http\Cookie;
use TheFairLib\Http\Response\BigPipe;
use TheFairLib\Http\Response\Page;
use TheFairLib\Utility\Utility;

class Controller extends Base
{
    /**
     * @var Page
     */
    protected static $_responseObj = false;
    protected function init(){
        if(self::$_responseObj === false){
            self::$_responseObj = new Page(new \stdClass());
        }
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

    public function showBPPage($pageName){
        if(!self::$_responseObj instanceof BigPipe){
            self::$_responseObj = new BigPipe();
        }
        $pageClassName = ucfirst($this->getRequest()->getModuleName())."\\Page\\".ucfirst($this->getRequest()->getControllerName())."\\".ucfirst($pageName);
        self::$_responseObj->setPage($pageClassName);
        $this->_setResponse(self::$_responseObj->send());
    }

    public function assign($varName, $varValue){
        return $this->getView()->assign($varName, $varValue);
    }

    public function display($actionName = '', $varArray = [])
    {
        if(empty($actionName)){
            $actionName = $this->getRequest()->getActionName();
        }
        $cookies = Utility::getResponseCookie();
        if(!empty($cookies)){
            foreach ($cookies as $cookie) {
                if($cookie instanceof Cookie){
                    setcookie($cookie->getName(), $cookie->getValue(),
                        $cookie->getExpire(), $cookie->getPath(),
                        $cookie->getDomain(), $cookie->getSecure(),
                        $cookie->getHttpOnly());
                }
            }
        }
        parent::display($actionName, $varArray);
    }

    /**
     * 判断是否为AJAX请求
     *
     * @return bool
     */
    public function isAjax(){
        return $this->getRequest()->isXmlHttpRequest();
    }

    public function redirect($url)
    {
        self::$_responseObj->redirect($url);
    }
}