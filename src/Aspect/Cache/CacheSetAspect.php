<?php

declare(strict_types=1);

namespace TheFairLib\Aspect\Cache;

use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\AnnotationManager;
use Hyperf\Cache\CacheManager;
use Hyperf\Cache\Driver\KeyCollectorInterface;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use Hyperf\Di\Exception\Exception;
use Psr\SimpleCache\InvalidArgumentException;
use TheFairLib\Annotation\CacheSet;

/**
 * @Aspect
 */
class CacheSetAspect extends AbstractAspect
{
    public $classes = [];

    public $annotations = [
        CacheSet::class,
    ];

    /**
     * @var CacheManager
     */
    protected $manager;

    /**
     * @var AnnotationManager
     */
    protected $annotationManager;

    public function __construct(CacheManager $manager, AnnotationManager $annotationManager)
    {
        $this->manager = $manager;
        $this->annotationManager = $annotationManager;
    }

    /**
     * @throws Exception
     * @throws InvalidArgumentException
     */
    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $className = $proceedingJoinPoint->className;
        $method = $proceedingJoinPoint->methodName;
        $arguments = $proceedingJoinPoint->arguments['keys'];

        [$key, $ttl, $group, $annotation] = $this->annotationManager->getCacheSetValue($className, $method, $arguments);

        $driver = $this->manager->getDriver($group);

        [$has, $result] = $driver->fetch($key);
        if ($has) {
            return $result;
        }

        $result = $proceedingJoinPoint->process();
        if (!empty($result)) {
            $driver->set($key, $result, $ttl);
            if ($driver instanceof KeyCollectorInterface && $annotation instanceof Cacheable && $annotation->collect) {
                $driver->addKey($annotation->prefix . 'MEMBERS', $key);
            }
        }


        return $result;
    }
}
