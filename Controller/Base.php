<?php
/**
 * TheFairLib\Controller
 * Controller基类
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0.0
 */

namespace TheFairLib\Controller;

class Base extends \Yaf\Controller_Abstract
{
    /**
     * Controller初始化需要的操作
     */
    protected final function init(){
        //@todo do something
    }

    /**
     * 渲染接口返回结果
     *
     * @param mixed $result 结果集
     * @param string $msg 提示信息
     * @param int $code 提示代码
     */
    protected final function showResult($result, $msg = '', $code = 0){
        $this->_setRpcServiceResponse(array(
            'code' => $code,
            'message' => $msg,
            'result' => $result,
        ));
    }

    /**
     * 渲染接口返回的错误信息
     *
     * @param string $err 错误内容
     * @param int $code 错误编码
     * @param array $data 扩展错误信息
     */
    protected final function showError($err, $code = 10000, $data = array()){
        return self::showResult($data, $err, $code);
    }

    /**
     * 将接口的返回结果设置到Yaf得Response中
     *
     * @param mixed $content 接口返回结果
     */
    private function _setRpcServiceResponse($content){
        if(is_object($content) || is_array($content)){
           $content = json_encode($content);
        }
        $this->getResponse()->setBody($content);
    }
}
