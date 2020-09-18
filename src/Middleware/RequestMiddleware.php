<?php

declare(strict_types=1);

namespace TheFairLib\Middleware;

use TheFairLib\Contract\RequestParamInterface;
use TheFairLib\Exception\ServiceException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\HttpServer\Router\Dispatched;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Utils\Context;

/**
 * 接到客户端请求，通过该中间件进行一些调整
 *
 * Class RequestMiddleware
 * @package TheFairLib\Middleware
 */
class RequestMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var array
     */
    protected $implements;

    public function __construct(ContainerInterface $container, ServerRequestInterface $request, ConfigInterface $config)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 利用协程上下文存储请求开始的时间，用来计算程序执行时间
        Context::set('execution_start_time', microtime(true));

        $dispatched = $request->getAttribute(Dispatched::class);

        if (!$dispatched instanceof Dispatched) {
            throw new ServiceException(sprintf('The dispatched object is not a %s object.', Dispatched::class));
        }

        //初始化参数验证
        $this->container->get(RequestParamInterface::class)->initCoreValidation($dispatched);

        return $handler->handle($request);
    }
}
