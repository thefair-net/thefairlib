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

use Hyperf\HttpMessage\Server\Request as Psr7Request;
use Hyperf\HttpMessage\Uri\Uri;
use Hyperf\JsonRpc\ResponseBuilder;
use Hyperf\Server\ServerManager;
use Swoole\Server\Port;

class TcpServer extends \Hyperf\JsonRpc\TcpServer
{
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
}
