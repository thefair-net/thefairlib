<?php
/**
 * Base.php
 *
 * @author liumingzhi
 * @version 1.0
 * @copyright 2015-2015
 * @date 16/4/14 下午2:23
 */

namespace TheFairLib\Message;


use TheFairLib\Message\IM\RongCloud\RongCloud;

class Message
{
    static public $instance;

    /**
     * @return Message
     */
    static public function Instance()
    {
        $class = get_called_class();
        if (empty(self::$instance)) {
            self::$instance = new $class();
        }
        return self::$instance;
    }

    /**
     * @param string $server
     * @return RongCloud
     * @throws \Exception
     * @throws \Yaf\Exception
     */
    public function getApplication($server = 'RongCloud')
    {
        switch ($server) {
            case 'RongCloud' :
                return RongCloud::Instance();
                break;
        }
        throw new \Exception('error ', $server);
    }
}