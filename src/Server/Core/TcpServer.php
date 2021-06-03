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
use TheFairLib\Library\Logger\Logger;

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
    public function onReceive($server, int $fd, int $fromId, string $data): void
    {
        // 利用协程上下文存储请求开始的时间，用来计算程序执行时间
        Context::set('execution_start_time', microtime(true));
        parent::onReceive($server, $fd, $fromId, $data);
        try {
            $this->eventDispatcher->dispatch(new OnReceive($server, $fd, $fromId, $data));
        } catch (\Throwable $e) {
            Logger::get()->error('on_receive:event', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }

    /**
     * 重写 send, 目的是为了获得 body 的大小
     *
     * @param SwooleServer $server
     * @param int $fd
     * @param ResponseInterface $response
     */
    protected function send($server, int $fd, ResponseInterface $response): void
    {
        Context::set('server:response_body_size', $response->getBody()->getSize());
        $server->send($fd, (string)$response->getBody());
        try {
            if (arrayGet($this->serverConfig, 'name', '') === 'json-rpc') {
                Context::set('server:response_body', arrayGet($this->packer->unpack((string)$response->getBody()), 'result', ''));
            }
        } catch (\Throwable $e) {
            Logger::get()->error('on_receive_send', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);
        }
    }
}
