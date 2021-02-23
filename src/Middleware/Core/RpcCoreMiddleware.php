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

namespace TheFairLib\Middleware\Core;

use Closure;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\JsonRpc\ResponseBuilder;
use Hyperf\Rpc\Protocol;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TheFairLib\Library\Logger\Logger;

class RpcCoreMiddleware extends \Hyperf\JsonRpc\CoreMiddleware
{
    public function __construct(ContainerInterface $container, Protocol $protocol, ResponseBuilder $builder, string $serverName)
    {
        parent::__construct($container, $protocol, $builder, $serverName);
    }

    protected function handleFound(Dispatched $dispatched, ServerRequestInterface $request)
    {
        if ($dispatched->handler->callback instanceof Closure) {
            $response = call($dispatched->handler->callback);
        } else {
            [$controller, $action] = $this->prepareHandler($dispatched->handler->callback);
            $controllerInstance = $this->container->get($controller);
            if (!method_exists($controller, $action)) {
                // Route found, but the handler does not exist.
                return $this->responseBuilder->buildErrorResponse($request, ResponseBuilder::INTERNAL_ERROR);
            }
            $parameters = $this->parseMethodParameters($controller, $action, $dispatched->params);
            try {
                $response = $controllerInstance->{$action}(...$parameters);
            } catch (\Throwable $exception) {
                $response = $this->responseBuilder->buildErrorResponse($request, ResponseBuilder::SERVER_ERROR, $exception);
                $this->responseBuilder->persistToContext($response);

                throw $exception;
            }
        }
        return $response;
    }

    protected function handleNotFound(ServerRequestInterface $request)
    {
        return $this->responseBuilder->buildErrorResponse($request, ResponseBuilder::METHOD_NOT_FOUND);
    }

    protected function handleMethodNotAllowed(array $routes, ServerRequestInterface $request)
    {
        return $this->handleNotFound($request);
    }

    protected function transferToResponse($response, ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseBuilder->buildResponse($request, $response);
    }
}
