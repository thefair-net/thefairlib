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

class Isbn extends API
{
    private $_queryUrl = 'http://feedback.api.juhe.cn/ISBN';

    /**
     * @return Isbn
     */
    static public function Instance()
    {
        return parent::Instance();
    }

    protected function _getAppKey()
    {
        return Config::get_api_juhe('isbn.app_key');
    }

    public function getBookInfo($sub)
    {
        $param = [
            'sub' => $sub,
        ];
        return $this->_sendRequest($this->_queryUrl, $param);
    }
}
