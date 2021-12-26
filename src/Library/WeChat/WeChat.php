<?php
declare(strict_types=1);

namespace TheFairLib\Library\WeChat;

use EasyWeChat\OfficialAccount\Application;
use Hyperf\Utils\ApplicationContext;
use TheFairLib\Contract\WeChatFactoryInterface;

class WeChat
{
    /**
     * WeChat
     *
     * @param string $type
     * @param string $appLabel
     * @param string $category
     * @return Application|\EasyWeChat\MiniProgram\Application|\EasyWeChat\OpenPlatform\Application|\EasyWeChat\Payment\Application
     */
    public static function get(string $type, string $appLabel, string $category = 'thefair')
    {
        return container(WeChatFactoryInterface::class)->getApp($type, $appLabel, $category);
    }
}
