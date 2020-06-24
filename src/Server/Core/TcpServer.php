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
use Hyperf\Framework\Event\OnReceive;
use Hyperf\HttpMessage\Server\Request as Psr7Request;
use Hyperf\HttpMessage\Uri\Uri;
use Hyperf\JsonRpc\ResponseBuilder;
use Hyperf\Server\ServerManager;
use Hyperf\Utils\Context;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Swoole\Server as SwooleServer;
use Swoole\Server\Port;

class TcpServer extends \Hyperf\JsonRpc\TcpServer
{
    /**
     * @Inject
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * 用于重写 uri 路径
     *
     * @param int $fd
     * @param int $fromId
     * @param array $data
     * @return Psr7Request|ResponseInterface
     */
    protected function buildJsonRpcRequest(int $fd, int $fromId, array $data)
    {
        if (!isset($data['method'])) {
            $data['method'] = '';
        }
        if (!isset($data['params'])) {
            $data['params'] = [];
        }
        /** @var Port $port */
        [$type, $port] = ServerManager::get($this->serverName);
        $method = camelize($data['method']);

        $uri = (new Uri())->withPath($method)->withHost($port->host)->withPort($port->port);
        $request = (new Psr7Request('POST', $uri))->withAttribute('fd', $fd)
            ->withAttribute('fromId', $fromId)
            ->withAttribute('data', $data)
            ->withAttribute('request_id', $data['id'] ?? null)
            ->withParsedBody($data['params'] ?? '');
        $this->getContext()->setData($data['context'] ?? []);

        if (!isset($data['jsonrpc'])) {
            return $this->responseBuilder->buildErrorResponse($request, ResponseBuilder::INVALID_REQUEST);
        }
        return $request;
    }

    /**
     * 底层事件，用于系统日志
     *
     * @param SwooleServer $server
     * @param int $fd
     * @param int $fromId
     * @param string $data
     */
    public function onReceive(SwooleServer $server, int $fd, int $fromId, string $data): void
    {
        parent::onReceive($server, $fd, $fromId, $data);
        $this->eventDispatcher->dispatch(new OnReceive($server, $fd, $fromId, $data));
    }

    /**
     * 重写 send, 目的是为了获得 body 的大小
     *
     * @param SwooleServer $server
     * @param int $fd
     * @param ResponseInterface $response
     */
    protected function send(SwooleServer $server, int $fd, ResponseInterface $response): void
    {
        Context::set('server:response_body_size', $response->getBody()->getSize());
        $server->send($fd, (string)$response->getBody());
    }
}
