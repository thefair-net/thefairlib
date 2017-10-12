<?php

namespace TheFairLib\Aliyun\Alipay;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'wappay/service/AlipayTradeService.php';

class Base
{

    static public $instance;

    /**
     * @return Base
     */
    static public function Instance()
    {
        $class = get_called_class();
        if (empty(self::$instance[$class])) {
            self::$instance[$class] = new $class();
        }
        return self::$instance[$class];
    }

    /**
     * AOP SDK 入口文件
     *
     * @param $config
     * @return \AlipayTradeService
     */
    public function tradeService($config)
    {
        if (empty(self::$instance['trade_service'])) {

            self::$instance['trade_service'] = new \AlipayTradeService($config);
        }
        return self::$instance['trade_service'];
    }

    /**
     * 要使用的服务
     *
     * @param $name
     * @return \AlipayTradeQueryContentBuilder|\AlipayTradeFastpayRefundQueryContentBuilder|\AlipayTradeWapPayContentBuilder
     * @throws \Exception
     */
    public function setService($name)
    {
        $filePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . "wappay/buildermodel/{$name}.php";
        if (file_exists($filePath)) {
            require_once $filePath;
            $name = "\\{$name}";
            return new $name();
        } else {
            throw new \Exception('error file path: ' . $filePath);
        }
    }

}
