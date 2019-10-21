<?php
/**
 * TheFairLib\Controller
 * Controller基类
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0.0
 */

namespace TheFairLib\Controller;

use TheFairLib\Config\Config;
use TheFairLib\Http\Request;
use TheFairLib\Http\Response;
use TheFairLib\Utility\Utility;

abstract class Base extends \Yaf\Controller_Abstract
{
    protected static $instance;

    /**
     * 参数
     * @var array
     */
    protected static $_params;

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

    protected function _checkParams(){
        $commonParams = Config::get_params_common();

        $params = array();
        //初始化通用参数
        if(!empty($commonParams)){
            foreach($commonParams as $key => $conf){
                $this->_request->setParam($key, $this->_checkParam($key, $conf));
            }
        }
        //初始化特殊设置
        $funcName = strtolower('get_params_'.$this->_request->getModuleName().'_'.$this->_request->getControllerName());
        $specialParams = Config::$funcName(Request::getOriginalAction());

        if(!empty($specialParams)){
            foreach($specialParams as $key => $conf){
                if($this->_checkParamExist($key, $conf) === true){
                    $this->_request->setParam($key, $this->_checkParam($key, $conf));
                }
            }
        }

        static::$_params = $this->_request->getParams();
        Utility::set_requset_params(self::$_params);
        $_GET = $_POST = [];
    }

    protected function _checkParam($key, $paramConf){
        $value = Utility::getGpc($key, $paramConf['method'], $paramConf['type'], $paramConf['default']);
        if($value === NULL){
            throw new Exception('Parameter missing, '.$key);
        }
        if($key == 'item_per_page'){
            $value = min($value, 50);
        }
        if(!empty($paramConf['range']) && !in_array($value, $paramConf['range'])){
            throw new Exception('Parameter out of range, '.$key);
        }

        return $value;
    }

    protected function _checkParamExist($key, $paramConf){
        $ret = true;
        if(isset($paramConf['check_exist']) && $paramConf['check_exist'] === true){
            switch($paramConf['method']){
                case 'P':
                    $ret = isset($_POST[$key]);
                    break;
                case 'G':
                    $ret = isset($_GET[$key]);
                    break;
                default:
                    $ret = isset($_REQUEST[$key]);
            }
        }

        return $ret;
    }

    protected function _getControllerName(){
        $act = $this->_request->getActionName();
        $act = pathinfo($act);
        return $act['filename'];
    }
}
