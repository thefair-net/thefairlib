<?php
/**
 * BigPipe.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Http\Response;

use TheFairLib\BigPipe\Pagelet;
use TheFairLib\BigPipe\Render;
use TheFairLib\Http\Response;
use TheFairLib\Utility\Utility;

class BigPipe extends Response
{
    private $_result = array();

    private $_msg = '';

    private $_code = 0;

    private $_page = false;

    private $_pageRenderType = 'Streamline';

    private static $_jsonpCallbackName = 'callback';
    private static $_isJsonp = false;

    public function __construct(){
        parent::__construct();
    }

    public function getResult(){
        return $this->_result;
    }

    public function getMsg(){
        return $this->_msg;
    }

    public function getCode(){
        return $this->_code;
    }

    public function setResult($result){
        return $this->_result = $result;
    }

    public function setMsg($msg){
        return $this->_msg = $msg;
    }

    public function setCode($code){
        return $this->_code = $code;
    }

    public static function setCallBack($callback){
        return self::$_jsonpCallbackName = $callback;
    }

    public static function setIsJsonp($isJsonp){
        return self::$_isJsonp = $isJsonp;
    }

    protected function _serialize($content){
        $content = Utility::encode($content);

        if(self::$_isJsonp === true){
            $content = self::$_jsonpCallbackName . '(' . $content . ');';
        }

        return $content;
    }

    protected function _getContentType(){
        return 'text/html;charset=utf-8';
    }

    public function setPage($tplPath){
        $pageName = "\\BigPipe\\".str_replace(' ', '', ucwords(str_replace('_', ' ', $tplPath)));
        $this->_page = new $pageName();
    }

    /**
     * @return Pagelet
     */
    public function getPage(){
        return $this->_page;
    }

    public function setPageRenderType($renderType){
        $this->_pageRenderType = $renderType;
    }

    public function getPageRenderType(){
        return $this->_pageRenderType;
    }

    public function send(){
        $cookies = Utility::getResponseCookie();
        if(!empty($cookies)){
            foreach($cookies as $cookie){
                $this->setCookie($cookie);
            }
        }
        $this->_renderBigPipeBody();
        return '';
    }

    private function _renderBigPipeBody(){
        $render = Render::create($this->getPage(), $this->getPageRenderType());
        $render->render();
    }
}