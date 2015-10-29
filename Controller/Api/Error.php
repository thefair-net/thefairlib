<?php
/**
 * Error.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Controller\Api;

use TheFairLib\Controller\ErrorBase;
use \TheFairLib\Exception\Api\Exception as Exception;
use TheFairLib\Http\Response\Api;

class Error extends ErrorBase
{
    protected function _errorDefault(Exception $e){
        Controller::getInstance()->showError(
            new Api($e->getExtData(), $e->getMessage(), $e->getExtData(), $e->getHttpStatus())
        );
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

    protected function _DealIllegalRequest(){
        //@todo
    }

    protected function _DealNotfoundRequest(){
        Controller::getInstance()->showError(
            new Api(array(), 'Illegal Request', 40000, 404)
        );
    }
}