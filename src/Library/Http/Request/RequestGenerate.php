<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file RequestParam.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-09-17 17:57:00
 *
 **/

namespace TheFairLib\Library\Http\Request;

use Closure;
use FastRoute\Dispatcher;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Aop\Ast;
use Hyperf\Di\ReflectionManager;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\HttpServer\Router\RouteCollector;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionParameter;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TheFairLib\Exception\ServiceException;

class RequestGenerate extends RequestBase
{
    protected $param = [];

    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @var Ast
     */
    private $parser;

    public function __construct(ContainerInterface $container, ServerRequestInterface $request, ConfigInterface $config)
    {
        $this->parser = new Ast();
        parent::__construct($container, $request, $config);
    }

    /**
     * 自动生成 Request 文件
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function automaticallyGenerate(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $factory = $this->container->get(DispatcherFactory::class);

        /**
         * @var RouteCollector $router
         */
        $router = $factory->getRouter('http');//获得路由信息

        [$staticRouters, $variableRouters] = $router->getData();
        foreach ($staticRouters as $method => $items) {
            foreach ($items as $handler) {
                $this->analyzeHandler($method, $handler);
            }
        }
    }

    protected function analyzeHandler(string $methodType, Handler $handler)
    {
        [$className, $method] = $this->prepareHandler($handler->callback);
        $this->methodValidation($className, $method, $handler);
    }

    protected function methodValidation($className, $method, Handler $handler)
    {
        if ($this->checkWhitelist($handler->route)) {//过滤白单名
            return;
        }
        if ($this->checkBlacklist($method, $handler->route)) {//过滤黑单名
            return;
        }
        $keyName = "{$className}::{$method}";
        if (isset($this->param[$keyName])) {
            return;
        }

        if (preg_match('/[_\-]/', $method)) {
            throw new ServiceException('Do not use [_-]');
        }
        $reflectionClass = ReflectionManager::reflectClass($className);

        $this->parse($reflectionClass->getFileName());

        $reflectionMethod = ReflectionManager::reflectMethod($className, $method);
        /**
         * @var ReflectionParameter[] $parameters
         */
        $parameters = $reflectionMethod->getParameters();
        if (!empty($parameters)) {//不允许自带参数
            throw new ServiceException('Do not use param', $parameters);
        }

        $routePath = ltrim(bigCamelize($handler->route), '/');
        $requestClassName = sprintf('App\Request\%s', str_replace('/', '\\', $routePath));
        $requestClassPath = sprintf('%s/app/Request/%s.php', BASE_PATH, $routePath);

        if (!file_exists(dirname($requestClassPath))) {
            mkdir(dirname($requestClassPath) . '/', 0755, true);
            $this->output->writeln(sprintf('create dir [%s] .', dirname($requestClassPath)));
        }

        if (!file_exists($requestClassPath)) {
            file_put_contents($requestClassPath, $this->buildClass($requestClassName));
            $this->output->writeln(sprintf('create class [%s] file .', $requestClassPath));
        }
        if (file_exists(dirname($requestClassPath)) && file_exists($requestClassPath)) {
            $this->output->writeln(sprintf('success [%s] .', $requestClassName));
        }
        $this->param[$keyName] = true;
    }

    protected function parse($filename)
    {
//        $stmts = $this->parser->parse(file_get_contents($filename));
//        rd_debug($stmts);
//        exit();
//        $className = $this->parser->parseClassByStmts($stmts);
    }

    /**
     * $name
     *
     * @param string $name
     * @return string
     */
    protected function buildClass(string $name): string
    {
        $stub = file_get_contents(substr(__DIR__, 0, -21) . '/Command/Request/Stubs/Request.stub');
        $stub = $this->replaceNamespace($stub, $name);
        $stub = $this->replaceClass($stub, $name);
        return $stub;
    }

    /**
     * Replace the namespace for the given stub.
     * @param string $stub
     * @param string $name
     * @return string|string[]
     */
    protected function replaceNamespace(string &$stub, string $name)
    {
        $stub = str_replace(
            ['%NAMESPACE%'],
            [$this->getNamespace($name)],
            $stub
        );

        return $stub;
    }


    /**
     * Replace the class name for the given stub.
     * @param string $stub
     * @param string $name
     * @return string|string[]
     */
    protected function replaceClass(string &$stub, string $name)
    {
        $class = str_replace($this->getNamespace($name) . '\\', '', $name);

        $stub = str_replace('%CLASS%', $class, $stub);

        return $stub;
    }

    /**
     * Get the full namespace for a given class, without the class name.
     * @param string $name
     * @return string
     */
    protected function getNamespace(string $name): string
    {
        return trim(implode('\\', array_slice(explode('\\', $name), 0, -1)), '\\');
    }
}
