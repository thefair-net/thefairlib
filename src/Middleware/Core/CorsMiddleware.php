<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file CorsMiddleware.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-03-21 09:51:00
 *
 **/


declare(strict_types=1);

namespace TheFairLib\Middleware\Core;

use Hyperf\Context\Context;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * 解决跨域
 *
 * Class CorsMiddleware
 * @package App\Middleware
 */
class CorsMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * 跨域
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('origin');
        if ($this->isOrigin($origin)) {//如果没有配置，默认就是全局跨域
            $response = Context::get(ResponseInterface::class);
            $response = $response->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Credentials', 'true')
                // Headers 可以根据实际情况进行改写。
                ->withHeader('Access-Control-Allow-Headers', $request->getHeader('access-control-request-headers'));

            Context::set(ResponseInterface::class, $response);

            if ($request->getMethod() == 'OPTIONS') {
                return $response;
            }
        }

        return $handler->handle($request);
    }

    /**
     * 判断是否跨域
     *
     * @param string $originHost
     * @return bool
     */
    protected function isOrigin(string $originHost): bool
    {
        $origin = config('auth.cors.origin', []);
        if (empty($origin)) {
            return true;
        }
        return in_array($originHost, $origin);
    }
}
