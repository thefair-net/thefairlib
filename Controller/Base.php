<?php
/**
 * TheFairLib\Controller
 * Controller基类
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0.0
 */

namespace TheFairLib\Controller;

use TheFairLib\Http\Response;

abstract class Base extends \Yaf\Controller_Abstract
{
    protected static $instance;

    /**
     * Controller初始化需要的操作
     */
    abstract protected function init();

    /**
     * 显示正确结果
     *
     * @return mixed
     */
    public function showResult($response){

    }

    public function showError($response)
    {

    }

    final protected function _setResponse($content){
        $this->getResponse()->setBody($content);
    }

    /**
     * @return null
     */
    public static function getInstance(){
        if (is_null(static::$instance)) static::$instance = new static();
        return static::$instance;
    }
}
