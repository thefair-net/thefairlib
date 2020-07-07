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
use Psr\EventDispatcher\EventDispatcherInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use TheFairLib\Event\OnRequest;

class HttpServer extends \Hyperf\HttpServer\Server
{

    /**
     * @Inject
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function onRequest(SwooleRequest $request, SwooleResponse $response): void
    {
        parent::onRequest($request, $response);
        $this->eventDispatcher->dispatch(new OnRequest($request, $response));
    }

}
