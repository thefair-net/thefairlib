<?php
/**
 * JuHe.php
 *
 * @author liumingzhi
 * @version 1.0
 * @copyright 2015-2015
 * @date 16/10/10 下午5:23
 */
namespace TheFairLib\Juhe\Src;

use TheFairLib\Config\Config;
use TheFairLib\Http\Curl;

class Logistics
{
    private $_queryUrl = 'http://v.juhe.cn/exp/index';

    private $_comUrl = 'http://v.juhe.cn/exp/com';

    static public $instance;

    /**
     * @return Logistics
     */
    static public function Instance()
    {
        $class = get_called_class();
        if (empty(self::$instance[$class])) {
            self::$instance[$class] = new $class();
        }
        return self::$instance[$class];
    }

    private function _getAppKey()
    {
        return Config::get_order_logistics('default.app_key');
    }

    public function getLogisticsInfo($logisticsId, $companyName)
    {
        $result = [];
        switch (true) {
            case $this->_getCompanyName($companyName):
                $curl = new Curl();
                $param = [
                    'key' => $this->_getAppKey(),
                    'com' => $companyName,
                    'no' => $logisticsId,
                ];
                $curl->get($this->_queryUrl, $param);
                if (!empty($curl->response)) {
                    $result = json_decode($curl->response, true);
                }
                break;
        }
        return $result;
    }

    private function _getCompanyName($companyName)
    {
        //[{"com":"顺丰","no":"sf"},{"com":"申通","no":"sto"},{"com":"圆通","no":"yt"},{"com":"韵达","no":"yd"},{"com":"天天","no":"tt"},{"com":"EMS","no":"ems"},{"com":"中通","no":"zto"},{"com":"汇通","no":"ht"},{"com":"全峰","no":"qf"},{"com":"德邦","no":"db"}]
        return in_array($companyName, ['sf', 'sto', 'yt', 'yd', 'tt', 'ems', 'zto', 'ht', 'qf', 'db']);
    }
}
