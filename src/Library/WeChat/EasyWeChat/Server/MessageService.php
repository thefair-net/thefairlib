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

use EasyWeChat\Kernel\Exceptions\BadRequestException;
use EasyWeChat\Kernel\Exceptions\InvalidArgumentException;
use EasyWeChat\Kernel\Exceptions\InvalidConfigException;
use EasyWeChat\MiniProgram\Application;
use Hyperf\Contract\ContainerInterface;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Utils\Context;
use ReflectionException;
use TheFairLib\Annotation\Doc;
use TheFairLib\Constants\WeChatBase;
use TheFairLib\Contract\WeChatFactoryInterface;
use TheFairLib\Exception\ServiceException;

abstract class MessageService
{

    /**
     * @var WeChatFactoryInterface
     */
    protected $weChatFactory;

    /**
     * @var string
     */
    protected $category = 'thefair';

    /**
     * @var string
     */
    protected $weChatType = WeChatBase::OFFICIAL;

    /**
     * @var string
     */
    protected $appLabel;

    /**
     * @Doc(name="服务启动时，实例化到容器中，每一个公号只初始化一次，这样性能会大大提高，需要从写 request 数据，不然是天坑。")
     *
     * MessageService constructor.
     * @param ContainerInterface $container
     * @param WeChatFactoryInterface $weChatFactory
     */
    public function __construct(ContainerInterface $container, WeChatFactoryInterface $weChatFactory)
    {
        if (empty($this->appLabel)) {
            throw new ServiceException(sprintf('%s config info error ', $this->appLabel));
        }
        $this->app = $weChatFactory->getApp($this->weChatType, $this->appLabel, $this->category);
        $this->weChatFactory = $weChatFactory;
    }


    abstract public function handler($message);

    /**
     * @var
     */
    protected $app;

    /**
     * @param RequestInterface $request
     * @return false|string
     * @throws BadRequestException
     * @throws InvalidArgumentException
     * @throws InvalidConfigException
     * @throws ReflectionException
     */
    public function push(RequestInterface $request)
    {
        $this->getApp($request)->server->push(function ($message) {
            return $this->handler($message);
        });
        $response = $this->getApp($request)->server->serve();
        return $response->getContent();
    }

    /**
     * @Doc(name="每次重写 request , 核心代码，非常重要")
     *
     * @param RequestInterface|null $request
     * @return Application|\EasyWeChat\OfficialAccount\Application|\EasyWeChat\OpenPlatform\Application
     */
    public function getApp(RequestInterface $request = null)
    {
        $id = __CLASS__ . sprintf(':%s.%s.%s', $this->appLabel, $this->category, $this->weChatType);
        if (!Context::has($id) && $request = $this->weChatFactory->setRequest($request)) {
            $this->app->rebind('request', $request);
            Context::set($id, true);
        }
        return $this->app;
    }

    /**
     * @param $app
     * @return Application|\EasyWeChat\OfficialAccount\Application|\EasyWeChat\OpenPlatform\Application
     */
    public function setApp($app)
    {
        $this->app = $app;
        return $app;
    }
}
