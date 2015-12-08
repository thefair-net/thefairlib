<?php
/**
 * Push.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Mobile\Push;

use TheFairLib\Mobile\Push\Ext\GeTui\GeTui;

class Push
{
    private $_pushService = '';
    static public $instance;

    public function __construct($pushService = 'getui'){
        switch($pushService){
            case 'getui':
                $class = new GeTui();
                break;
            default:
                throw new \Exception('push service not exist');
        }
        return $class;
    }

    static public function Instance($pushService = 'getui')
    {
        if (empty(self::$instance[$pushService])) {
            self::$instance[$pushService] = new static($pushService);
        }
        return self::$instance[$pushService];
    }

//    public function sendPushToSingle
}