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
use TheFairLib\Http\Response;
use TheFairLib\Http\Response\Api;

class Controller extends Base
{
    protected function init(){

    }

    public function showResult(Api $response){
        $this->_setResponse($response->send());
    }

    public function showError(Api $response){
        if(empty($response->getCode())){
            $response->setCode(10000);
        }
        $this->showResult($response);
    }

}