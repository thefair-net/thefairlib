<?php

namespace TheFairLib\Verify;

use TheFairLib\Config\Config;

class Mobile
{

    static public $instance;

    /**
     * @return Mobile
     */
    static public function Instance()
    {
        $class = get_called_class();
        if (empty(self::$instance[$class])) {
            $obj = new $class();
            self::$instance[$class] = $obj->_setOptions();
        }
        return self::$instance[$class];
    }

    /**
     * 配置短信发送
     *
     * @return mixed
     * @throws Exception
     * @throws \TheFairLib\Config\Exception
     */
    private function _setOptions()
    {
        $config = (array)Config::load('Verify');
        //如果默认手机验证码提供商为空，或手机验证码提供商不在列表内
        if (!isset($config['mobileVerify']) || !isset($config['mobileVerifyList']) || !in_array($config['mobileVerify']['name'], $config['mobileVerifyList'])) {
            throw new Exception('common.mobileVerify error');
        }
        $class = "\\TheFairLib\\Verify\\Mobile\\" . $config['mobileVerify']['name'];
        return new $class;
    }

}
