<?php
/**
 * ErrorBase.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */

namespace TheFairLib\Controller;

abstract class ErrorBase extends Base
{
    /**
     * 继承base中的初始化处理
     */
    protected function init(){
        //@todo
    }

    /**
     * Yaf默认异常处理action
     * 启用error处理ctrl的方法：
     *
     * $app  = new Yaf\Application(APP_PATH . "/config/application.ini");
     * $app->getDispatcher()->catchException(true);
     * $app->bootstrap()->run();
     *
     * @param \Exception $exception
     */
    public function errorAction(\Exception $exception){
        if($exception instanceof \Yaf\Exception){
            switch ($exception->getCode()) {
                case \Yaf\ERR\NOTFOUND\MODULE:
                    $this->_errorNotfoundModule($exception);
                    break;
                case \Yaf\ERR\NOTFOUND\CONTROLLER:
                    $this->_errorNotfoundController($exception);
                    break;
                case \Yaf\ERR\NOTFOUND\ACTION:
                    $this->_errorNotfoundAction($exception);
                    break;
                case \Yaf\ERR\NOTFOUND\VIEW:
                    $this->_errorNotfoundView($exception);
                    break;
                default :
                    $this->_errorDefault($exception);
                    break;
            }
        }else{
            $this->_errorDefault($exception);
        }

    }

    /**
     * 处理通用异常信息
     *
     * @param \Exception $e
     * @throws \Exception
     */
    protected function _errorDefault(\Exception $e){
        $this->_DealWithException($e);
    }

    /**
     * 当module找不到时的默认异常处理
     *
     * @param \Yaf\Exception $e
     * @throws \Exception
     */
    protected function _errorNotfoundModule(\Yaf\Exception $e){
        $this->_DealWithException($e);
    }

    /**
     * 当controller找不到时的默认异常处理
     *
     * @param \Yaf\Exception $e
     * @throws \Exception
     */
    protected function _errorNotfoundController(\Yaf\Exception $e){
        $this->_DealWithException($e);
    }

    /**
     * 当action找不到时的默认异常处理
     *
     * @param \Yaf\Exception $e
     * @throws \Exception
     */
    protected function _errorNotfoundAction(\Yaf\Exception $e){
        $this->_DealWithException($e);
    }

    /**
     * 当视图找不到时的默认异常处理
     *
     * @param \Yaf\Exception $e
     * @throws \Exception
     */
    protected function _errorNotfoundView(\Yaf\Exception $e){
        $this->_DealWithException($e);
    }

    /**
     * 对默认异常不做任何处理直接抛出
     *
     * @param \Exception $e
     * @throws \Exception
     */
    private function _DealWithException(\Exception $e){
        throw $e;
    }
}