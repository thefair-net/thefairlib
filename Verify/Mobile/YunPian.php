<?php
/**
 * Created by PhpStorm.
 * User: liumingzhi
 * Date: 15/10/31
 * Time: 下午11:19
 */

namespace TheFairLib\Verify\Mobile;

use TheFairLib\Config\Config;
use TheFairLib\Http\Curl;
use TheFairLib\Utility\Utility;
use TheFairLib\Verify\Exception;
use TheFairLib\Verify\Mobile\Inter\Sms;

class YunPian implements Sms
{
    const SEND_URL = 'http://yunpian.com/v1/sms/send.json';
    const SEND_URL_TPL = 'http://yunpian.com/v1/sms/tpl_send.json';

    private $_appKey;

    public function __construct()
    {
        $config = Config::get_verify();
        if(!isset($config['appKey']) || empty($config['appKey']['YunPian']['key'])) {
            throw new Exception('common.appKey error');
        }
        $this->_appKey = $config['appKey']['YunPian']['key'];
    }

    /**
     * 单条信息
     *
     * @param $mobile
     * @param $msg
     * @return null
     * @throws Exception
     */
    public function sendMessage($mobile, $msg)
    {
        if (!Utility::isMobile($mobile)) {
            throw new Exception('error mobile :' . $mobile);
        }
        if (empty($msg)) {
            throw new Exception('`msg` is not null');
        }

        $data = array(
            'apikey' => $this->_appKey,
            'mobile' => $mobile,
            'text' => $msg,
        );
        $curl = new Curl();
        $curl->post(self::SEND_URL,$data);
        return $curl->response;
    }

    /**
     * 群发信息
     *
     * @param $mobile
     * @param $msg
     */
    public function sendMessageList($mobile, $msg)
    {
        if (is_array($mobile)) {
            throw new Exception('mobile is array');
        }
        if (empty($msg)) {
            throw new Exception('`msg` is not null');
        }

    }
}