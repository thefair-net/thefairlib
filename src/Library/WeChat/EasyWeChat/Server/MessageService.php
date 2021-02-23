<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file Message.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-11-22 11:00:00
 *
 **/

namespace TheFairLib\Library\WeChat\EasyWeChat\Server;

use App\Service\ThirdParty\EasyWeChat\WeChatFactoryService;
use EasyWeChat\Kernel\Exceptions\BadRequestException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use Hyperf\Di\Annotation\Inject;
use ReflectionException;

abstract class MessageModel
{

    /**
     * @Inject()
     * @var WeChatFactoryService
     */
    protected $weChatFactory;

    /**
     * @var string
     */
    protected $category = 'thefair';

    /**
     * @var string
     */
    protected $weChatType;

    /**
     * @var string
     */
    protected $appLabel;

    abstract public function handler($message);

    abstract public function event($message);

    /**
     * @return false|string
     * @throws BadRequestException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws ReflectionException
     */
    protected function push()
    {
        $app = $this->weChatFactory->getApp($this->weChatType, $this->appLabel, $this->category);
        $app->server->push(function ($message) {
            return $this->handler($message);
        });
        $response = $app->server->serve();
        return $response->getContent();
    }
}
