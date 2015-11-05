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
use TheFairLib\Exception\Api\ApiException;
use \Yaf\Exception as Exception;
use TheFairLib\Http\Response\Api;

class Error extends ErrorBase
{
    protected function _errorDefault(Exception $e){
        if($e instanceof ApiException){
            $this->showError(
                new Api($e->getExtData(), $e->getMessage(), $e->getExtData(), $e->getHttpStatus())
            );
        }else{
            $this->_DealIllegalRequest();
        }

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
        $this->showError(
            new Api(array(), 'Illegal Request', 40000, 404)
        );
    }

    protected function _DealNotfoundRequest(){
        $this->showError(
            new Api(array(), 'Illegal Request', 40000, 404)
        );
    }
}