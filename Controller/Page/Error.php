<?php
/**
 * Error.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Controller\Page;

use TheFairLib\Controller\ErrorBase;
use TheFairLib\Exception\Page\PageException;
use TheFairLib\Http\Response\Page;
use TheFairLib\Logger\Logger;
use TheFairLib\Utility\Utility;
use \Yaf\Exception as Exception;
use Yaf\Registry;


abstract class Error extends ErrorBase
{
    /**
     * @var Page
     */
    protected static $_responseObj = false;

    protected function init()
    {
        \Yaf\Dispatcher::getInstance()->autoRender(false);
        if (self::$_responseObj === false) {
            self::$_responseObj = new Page(new \stdClass());
        }
    }

    protected function _errorDefault(\Exception $e)
    {
        if ($e instanceof PageException) {
            $this->showError(
                $e->getMessage(), $e->getExtData(), $e->getExtCode(), $e->getHttpStatus()
            );
        } else {
            if (defined('APP_NAME')) {
                Logger::Instance()->error(date("Y-m-d H:i:s +u") . "\n"
                    . "来源IP:{$_SERVER['REMOTE_ADDR']}\n"
                    . "请求接口:{$_SERVER['REQUEST_URI']}\n"
                    . "请求Cookie:" . Utility::encode($_COOKIE) . "\n"
                    . "请求参数:" . Utility::encode($_REQUEST) . "\n"
                    . "错误信息:" . $e->getMessage() . "\n"
                    . "Trace:" . $e->getTraceAsString() . "\n\n");
            }
            if ($this->isAjax()) {
                $this->showError($e->getMessage());
            } else {
                $this->_DealIllegalRequest($e->getMessage());
            }
        }

    }

    protected function _errorNotfoundModule(Exception $e)
    {
        $this->_DealNotfoundRequest();
    }

    protected function _errorNotfoundController(Exception $e)
    {
        $this->_DealNotfoundRequest();
    }

    protected function _errorNotfoundAction(Exception $e)
    {
        $this->_DealNotfoundRequest();
    }

    protected function _errorNotfoundView(Exception $e)
    {
        $this->_DealNotfoundRequest();
    }

    protected function _DealIllegalRequest($msg = '')
    {
        header("Content-type: text/html; charset=utf-8");
        header("status: 500 Internal Server Error");
        if (Registry::get('config')->phase == 'prod') {
            $this->assign('error', $msg);
            $this->display("500");
        } else {
            echo $msg;
        }
    }

    protected function _DealNotfoundRequest()
    {
        header("Content-type: text/html; charset=utf-8");
        header("status: 404 Not Found");
        $this->display("404");
    }

    public function showResult($result, $msg = '', $code = '0')
    {
        self::$_responseObj->setCode($code);
        self::$_responseObj->setMsg($msg);
        self::$_responseObj->setResult($result);
        $this->_setResponse(self::$_responseObj->send());
    }

    public function showError($error, $result = array(), $code = '40001', $httpCode = 400)
    {
        self::$_responseObj->setHttpCode($httpCode);
        if ($this->isAjax()) {
            $this->showResult($result, $error, $code);
        } else {
            $this->assign('error', $error);
            $this->assign('code', $code);
            $this->assign('result', $result);
            $this->display('500');
        }
    }

    public function assign($varName, $varValue)
    {
        return $this->getView()->assign($varName, $varValue);
    }

    public function display($actionName = '', $varArray = [])
    {
        if (empty($actionName)) {
            $actionName = $this->getRequest()->getActionName();
        }
        parent::display($actionName, $varArray);
    }

    /**
     * 判断是否为AJAX请求
     *
     * @return bool
     */
    public function isAjax()
    {
        return $this->getRequest()->isXmlHttpRequest();
    }
}