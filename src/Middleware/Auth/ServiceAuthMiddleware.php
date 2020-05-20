<?php

declare(strict_types=1);

namespace TheFairLib\Middleware\Auth;

use TheFairLib\Exception\ServiceException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ServiceAuthMiddleware implements MiddlewareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }

    /**
     * 验证请求是否合法
     *
     * @param $authData
     * @return bool
     */
    protected function checkAuthorize($authData)
    {
        if (empty($authData['app_key']) || empty($authData['app_secret'])) {
            throw new ServiceException('auth config is error');
        }

        if ($authData['app_secret'] != md5(md5($authData['app_key']))) {
            throw new ServiceException('authorize field');
        }
        return true;
    }
}
