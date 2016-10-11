<?php
/**
 * Weather.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Juhe\Src;

use TheFairLib\Config\Config;

class Weather extends API
{
    private $_queryUrl = 'http://op.juhe.cn/onebox/weather/query';

    /**
     * @return Weather
     */
    static public function Instance()
    {
        return parent::Instance();
    }

    protected function _getAppKey()
    {
        return Config::get_api_juhe('weather.app_key');
    }

    public function getWeatherInfoByCity($cityName)
    {
        $param = [
            'cityname' => $cityName,
        ];
        return $this->_sendRequest($this->_queryUrl, $param);
    }
}
