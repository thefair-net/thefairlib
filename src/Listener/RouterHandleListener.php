<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Listener;

use Doctrine\Common\Annotations\AnnotationReader;
use Hyperf\Di\Annotation\AnnotationInterface;
use Hyperf\Di\ReflectionManager;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BeforeMainServerStart;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\RouteCollector;
use Hyperf\RpcServer\Event\AfterPathRegister;
use Hyperf\Utils\ApplicationContext;
use Throwable;

class RouterHandleListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function listen(): array
    {
        return [
            BeforeMainServerStart::class,
            AfterPathRegister::class,
        ];
    }

    public function process(object $event)
    {
//        if ($event instanceof AfterPathRegister) {
//            $annotation = $event->annotation;
//            if (!in_array($annotation->protocol, ['jsonrpc', 'jsonrpc-http', 'jsonrpc-tcp-length-check'])) {
//                return;
//            }
//            $metadata = $event->toArray();
//            $annotationArray = $metadata['annotation'];
//            unset($metadata['path'], $metadata['annotation'], $annotationArray['name']);
//            $metadata = array_merge($metadata, $annotationArray);
//            $className = $metadata['className'];
//            rd_debug([
//                $annotation->name,
//                $event->path,
//                unCamelize($event->path),
//                $metadata,
//            ]);
//            $reader = new AnnotationReader();
//            $reflectionClass = ReflectionManager::reflectClass($className);
//            $classAnnotations = $reader->getClassAnnotations($reflectionClass);
////            rd_debug([
////                $reflectionClass->getDocComment(),
////                $reflectionClass->getProperties(),
////                $reflectionClass->getMethods(),
////            ]);
//            if (!empty($classAnnotations)) {
//                foreach ($classAnnotations as $classAnnotation) {
//                    if ($classAnnotation instanceof AnnotationInterface) {
//                        $classAnnotation->collectClass($className);
//                    }
//                }
//            }
//
//            // Parse properties annotations.
//            $properties = $reflectionClass->getProperties();
//            foreach ($properties as $property) {
//                $propertyAnnotations = $reader->getPropertyAnnotations($property);
//                if (!empty($propertyAnnotations)) {
//                    foreach ($propertyAnnotations as $propertyAnnotation) {
//                        if ($propertyAnnotation instanceof AnnotationInterface) {
//                            $propertyAnnotation->collectProperty($className, $property->getName());
//                        }
//                    }
//                }
//            }
//
//            // Parse methods annotations.
//            $methods = $reflectionClass->getMethods();
//            foreach ($methods as $method) {
//                $methodAnnotations = $reader->getMethodAnnotations($method);
//                if (!empty($methodAnnotations)) {
//                    foreach ($methodAnnotations as $methodAnnotation) {
//                        if ($methodAnnotation instanceof AnnotationInterface) {
//                            $methodAnnotation->collectMethod($className, $method->getName());
//                        }
//                    }
//                }
//            }
//        }
//        $container = ApplicationContext::getContainer();
//        $router['http'] = $container->get(DispatcherFactory::class)->getRouter('http');
    }
}
