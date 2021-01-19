<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Server\Core;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;
use Psr\EventDispatcher\EventDispatcherInterface;
use TheFairLib\Event\OnRequest;

class HttpServer extends \Hyperf\HttpServer\Server
{

    /**
     * @Inject
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function onRequest($request, $response): void
    {
        // 利用协程上下文存储请求开始的时间，用来计算程序执行时间
        Context::set('execution_start_time', microtime(true));
        parent::onRequest($request, $response);
        $this->eventDispatcher->dispatch(new OnRequest($request, $response));
    }
}
