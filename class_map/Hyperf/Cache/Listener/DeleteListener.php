<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Hyperf\Cache\Listener;

use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Cache\AnnotationManager;
use Hyperf\Cache\CacheManager;
use Hyperf\Cache\Driver\DriverInterface;
use Hyperf\Cache\Driver\KeyCollectorInterface;
use Hyperf\Cache\Exception\CacheException;
use Hyperf\Event\Contract\ListenerInterface;
use Psr\SimpleCache\InvalidArgumentException;
use TheFairLib\Annotation\CacheSet;

class DeleteListener implements ListenerInterface
{
    /**
     * @var CacheManager
     */
    protected $manager;

    protected $annotationManager;

    public function __construct(CacheManager $manager, AnnotationManager $annotationManager)
    {
        $this->manager = $manager;
        $this->annotationManager = $annotationManager;
    }

    public function listen(): array
    {
        return [
            DeleteEvent::class,
        ];
    }

    /**
     * @param object $event
     * @throws InvalidArgumentException
     */
    public function process(object $event)
    {
        $className = $event->getClassName();
        $method = $event->getMethod();
        $arguments = $event->getArguments();
        try {
            [$key, , $group, $annotation] = $this->annotationManager->getCacheableValue($className, $method, $arguments);
        } catch (CacheException $e) {
            [$key, , $group, $annotation] = $this->annotationManager->getCacheSetValue($className, $method, $arguments);
        }
        /** @var DriverInterface $driver */
        $driver = $this->manager->getDriver($group);
        $driver->delete($key);

        if ($driver instanceof KeyCollectorInterface && ($annotation instanceof Cacheable || $annotation instanceof CacheSet) && $annotation->collect) {
            $driver->delKey($annotation->prefix . 'MEMBERS', $key);
        }
    }
}
