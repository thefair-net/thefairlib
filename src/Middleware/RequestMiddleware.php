<?php

declare(strict_types=1);

namespace TheFairLib\Middleware;

use TheFairLib\Exception\ServiceException;
use Closure;
use FastRoute\Dispatcher;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\ReflectionManager;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Utils\Collection;
use Hyperf\Validation\Contract\ValidatesWhenResolved;
use Hyperf\Validation\Middleware\ValidationMiddleware;
use Hyperf\Validation\Request\FormRequest;
use Hyperf\Validation\UnauthorizedException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Hyperf\Utils\Context;
use ReflectionParameter;

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
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var array
     */
    protected $implements;

    public function __construct(ContainerInterface $container, ServerRequestInterface $request, ConfigInterface $config)
    {
        $this->container = $container;
        $this->request = $request;
        $this->config = $config;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 利用协程上下文存储请求开始的时间，用来计算程序执行时间
        Context::set('execution_start_time', microtime(true));

        $dispatched = $request->getAttribute(Dispatched::class);

        if (!$dispatched instanceof Dispatched) {
            throw new ServiceException(sprintf('The dispatched object is not a %s object.', Dispatched::class));
        }

        $this->initCoreValidation($dispatched);

        return $handler->handle($request);
    }

    /**
     * 验证处理
     *
     * @param Dispatched $dispatched
     */
    private function initCoreValidation(Dispatched $dispatched)
    {
        $this->checkUrlBlacklist($dispatched);
        $this->checkValidityRouteRequest($dispatched);
        $this->autoValidateRequest($dispatched);
    }

    /**
     * 过滤系统保留关键字，或保留路由
     *
     * @param Dispatched $dispatched
     */
    protected function checkUrlBlacklist(Dispatched $dispatched): void
    {
        if ($this->shouldHandle($dispatched)) {
            [, $method] = $this->prepareHandler($dispatched->handler->callback);

            $blacklist = $this->config->get('auth.url_blacklist.system_reserved', []);

            if (arrayGet($blacklist, 'method') && in_array(unCamelize($method), arrayGet($blacklist, 'method'))) {
                throw new ServiceException('system reserved method');
            }

            if (arrayGet($blacklist, 'route') && in_array(unCamelize($dispatched->handler->route), arrayGet($blacklist, 'route'))) {
                throw new ServiceException('system reserved route');
            }
        }
    }

    /**
     * 强制添加参数验证文件
     *
     * @param Dispatched $dispatched
     */
    protected function checkValidityRouteRequest(Dispatched $dispatched): void
    {
        if ($this->shouldHandle($dispatched)) {
            $routePath = ltrim(bigCamelize($dispatched->handler->route), '/');

            $whitelist = $this->config->get('auth.url_whitelist', []);

            //如果在路由白名单里面，就不做参数、路由的强制验证
            if (arrayGet($whitelist, 'route') && in_array(unCamelize($dispatched->handler->route), arrayGet($whitelist, 'route'))) {
                $this->setIsAutoValidate(false);//不做验证
                return;
            }

            if (count(explode('/', $routePath)) !== 3) {
                throw new ServiceException('route rule error');
            }

            $filePath = sprintf('%s/app/Request/%s.%s', BASE_PATH, $routePath, 'php');

            if (!file_exists($filePath)) {
                throw new ServiceException(sprintf('CONFIG FILE %s NOT FOUND', $filePath), [
                    'router' => $dispatched->handler->route,
                    'class' => $dispatched->handler->callback[0],
                    'method' => $dispatched->handler->callback[1],
                ]);
            }
        }
    }

    /**
     * 自动验证
     *
     * @param Dispatched $dispatched
     */
    protected function autoValidateRequest(Dispatched $dispatched): void
    {
        if ($this->shouldHandle($dispatched) && $this->getIsAutoValidate()) {
            try {
                [$requestHandler, $method] = $this->prepareHandler($dispatched->handler->callback);
                $reflectionMethod = ReflectionManager::reflectMethod($requestHandler, $method);

                /**
                 * @var ReflectionParameter[] $parameters
                 */
                $parameters = $reflectionMethod->getParameters();
                if (!empty($parameters)) {
                    foreach ($parameters as $parameter) {
                        rd_debug([$parameters, $dispatched->handler, bigCamelize($dispatched->handler->route), $method, $parameter->getClass(), $parameter->getType(), $parameter->getName()]);

                        if ($parameter->getType() === null) {
                            continue;
                        }
                        /**
                         * @var string $className
                         */
                        $className = $parameter->getType()->getName();

                        //是否参数上自带 request 验证类，就默认走 \Hyperf\Validation\Middleware\ValidationMiddleware::process
                        if ($this->isImplementedValidatesWhenResolved($className)) {
                            return;
                        }
                    }
                }

                $routePath = ltrim(bigCamelize($dispatched->handler->route), '/');
                $className = sprintf('App\Request\%s', str_replace('/', '\\', $routePath));
                rd_debug(['class' => $className]);
                if (!class_exists($className)) {
                    throw new ServiceException("Class {$className} not exist");
                }

                $classList = $this->defaultRequestValidation();
                $classList->prepend($className);

                foreach ($classList as $class) {
                    if (!$this->isImplementedValidatesWhenResolved($class)) {
                        throw new ServiceException(sprintf('error request, must implements %s', ValidatesWhenResolved::class), [
                            'class_name' => $class,
                        ]);
                    }
                    /**
                     * @var FormRequest $formRequest
                     */
                    $formRequest = $this->container->get($class);
                    $formRequest->validateResolved();
                }
            } catch (UnauthorizedException $e) {
                throw new ServiceException('This action is unauthorized.');
            }
        }
    }

    /**
     * 正常的路由信息才开始验证
     *
     * @param Dispatched $dispatched
     * @return bool
     * @see ValidationMiddleware::shouldHandle()
     */
    protected function shouldHandle(Dispatched $dispatched): bool
    {
        return $dispatched->status === Dispatcher::FOUND && !$dispatched->handler->callback instanceof Closure;
    }

    /**
     * 获得 class 与 method, App\Controller\V2\Test::getTest
     *
     * @param array|string $handler
     * @return array
     * @see \Hyperf\HttpServer\CoreMiddleware::prepareHandler()
     */
    protected function prepareHandler($handler): array
    {
        if (is_string($handler)) {
            if (strpos($handler, '@') !== false) {
                return explode('@', $handler);
            }
            return explode('::', $handler);
        }
        if (is_array($handler) && isset($handler[0], $handler[1])) {
            return $handler;
        }
        throw new ServiceException('Handler not exist.');
    }

    /**
     * 判断是否实现了验证类
     *
     * @param string $classname
     * @return bool
     * @see ValidationMiddleware::isImplementedValidatesWhenResolved()
     */
    public function isImplementedValidatesWhenResolved(string $classname): bool
    {
        if (!isset($this->implements[$classname]) && class_exists($classname)) {
            $implements = class_implements($classname);
            $this->implements[$classname] = in_array(ValidatesWhenResolved::class, $implements, true);
        }
        return $this->implements[$classname] ?? false;
    }

    /**
     * 必须这样写，不然在并发时，参数会被覆盖
     *
     * @param bool $value
     */
    private function setIsAutoValidate(bool $value)
    {
        Context::set(__CLASS__ . ':is_auto_validate', $value);
    }

    /**
     * @return bool
     */
    private function getIsAutoValidate(): bool
    {
        return (bool)Context::get(__CLASS__ . ':is_auto_validate', true);
    }

    /**
     * 默认需要验证的类，按顺序执行
     *
     * @return Collection
     */
    private function defaultRequestValidation(): Collection
    {
        return new Collection($this->config->get('validation.request', []));
    }
}
