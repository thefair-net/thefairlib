<?php
/**
 * Push.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Mobile\Push;

use TheFairLib\Mobile\Push\Ext\Getui\GeTui;
use TheFairLib\Mobile\Push\Ext\Jpush\Jpush;

class Push
{
    static public $instance;

    /**
     *
     * @param string $pushService
     * @return Jpush|GeTui
     * @throws \Exception
     */
    public function getPushWork($pushService = 'getui')
    {
        switch ($pushService) {
            case 'getui':
                $class = new GeTui();
                break;
            case 'jpush' :
                $class = new Jpush();
                break;
            default:
                throw new \Exception('push service not exist');
        }
        return $class;
    }

    static public function Instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

//    public function sendPushToSingle
}