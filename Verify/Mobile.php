<?php

namespace TheFairLib\Verify;

use TheFairLib\Config\Config;

class Mobile
{

    static public $instance;

    /**
     * @return \TheFairLib\Verify\Mobile\Inter\Sms
     */
    static public function Instance($config = [])
    {
        $class = get_called_class();
        if (empty(self::$instance[$class])) {
            self::$instance[$class] = (new $class($config))->_setOptions($config);
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
    private function _setOptions($config = [])
    {
        if(empty($config)){
            $config = Config::get_verify();
        }

        //如果默认手机验证码提供商为空，或手机验证码提供商不在列表内
        if (!isset($config['mobileVerify']) || !isset($config['mobileVerifyList']) || !in_array($config['mobileVerify']['name'], $config['mobileVerifyList'])) {
            throw new Exception('common.mobileVerify error');
        }
        $class = "\\TheFairLib\\Verify\\Mobile\\" . $config['mobileVerify']['name'];
        if (!class_exists($class)) {
            throw new Exception('is none' . $class);
        }
        return new $class($config);
    }

}
