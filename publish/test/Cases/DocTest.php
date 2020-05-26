<?php

declare(strict_types=1);

namespace HyperfTest\Cases;

use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\HttpServer\Router\RouteCollector;
use Hyperf\Utils\ApplicationContext;
use HyperfTest\HttpTestCase;
use TheFairLib\Annotation\Doc;

/**
 * @internal
 */
class DocTest extends HttpTestCase
{
    private static $doc = [];

    /**
     * 测试是否编写文档
     */
    public function testDoc()
    {
        printf("\n\n--------Test Doc started------------\n\n");

        $factory = ApplicationContext::getContainer()->get(DispatcherFactory::class);
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
        foreach ($variableRouters as $method => $items) {
            foreach ($items as $item) {
                if (is_array($item['routeMap'] ?? false)) {
                    foreach ($item['routeMap'] as $routeMap) {
                        $this->analyzeHandler($method, $routeMap[0]);
                    }
                }
            }
        }
        printf("\n--------Test Doc end------------\n\n");
    }

    protected function analyzeHandler(string $methodType, Handler $handler)
    {
        [$className, $method] = $this->prepareHandler($handler->callback);
        $this->classTest($className);
        $this->methodTest($className, $method);
    }

    /**
     * 测试 class 文档是否完成
     *
     * @param $className
     */
    private function classTest($className)
    {
        if (isset(self::$doc[$className])) {
            return;
        }
        $classDoc = AnnotationCollector::getClassAnnotation($className, Doc::class);
//        if ($classDoc && $classDoc instanceof Doc) {
//
//        }

        printf("Test Class '%s' started.\n", $className);

        $this->assertIsObject($classDoc);
        $this->assertIsString($classDoc->name);
        $this->assertIsString($classDoc->desc);
        $this->assertTrue(!empty($classDoc->name));
        $this->assertIsArray($classDoc->tag);

        printf("Test Class '%s' end.\n", $className);
        self::$doc[$className] = true;
    }

    /**
     * 测试 action 是否完成注释说明
     *
     * @param $className
     * @param $method
     */
    private function methodTest($className, $method)
    {
        $keyName = "{$className}::{$method}";
        if (isset(self::$doc[$keyName]) || in_array($method, [
                'showSuccess',
                'showError',
                'showResult',
                '__get',
                '__set',
            ])) {
            return;
        }
        printf("Test Method '%s::%s' started.\n", $className, $method);
        $classDoc = AnnotationCollector::getClassMethodAnnotation($className, $method);

//        if (isset($classDoc[Doc::class]) && $classDoc[Doc::class] instanceof Doc) {
//
//        }
        $classDoc = $classDoc[Doc::class];
        $this->assertIsObject($classDoc);
        $this->assertIsString($classDoc->name);
        $this->assertIsString($classDoc->desc);
        $this->assertTrue(!empty($classDoc->name));
        $this->assertIsArray($classDoc->tag);

        printf("Test Method '%s::%s' end.\n", $className, $method);
        self::$doc[$keyName] = true;
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
        return [];
    }
}
