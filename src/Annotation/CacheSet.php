<?php

declare(strict_types=1);

namespace TheFairLib\Annotation;

use Hyperf\Cache\CacheListenerCollector;
use Hyperf\Di\Annotation\AbstractAnnotation;
use Hyperf\Di\Annotation\AnnotationCollector;

/**
 * 透明缓存，有数据就配置，没数据就穿透，@todo 请谨慎使用
 *
 * @Annotation
 * @Target({"METHOD"})
 */
class CacheSet extends AbstractAnnotation
{
    /**
     * @var string
     */
    public $prefix;

    /**
     * @var string
     */
    public $value;

    /**
     * @var null|int
     */
    public $ttl;

    /**
     * @var string
     */
    public $listener;

    /**
     * The max offset for ttl.
     * @var int
     */
    public $offset = 0;

    /**
     * @var string
     */
    public $group = 'default';

    /**
     * @var bool
     */
    public $collect = false;

    public function __construct($value = null)
    {
        parent::__construct($value);
        if ($this->ttl !== null) {
            $this->ttl = (int) $this->ttl;
        }
        $this->offset = (int) $this->offset;
    }

    public function collectMethod(string $className, ?string $target): void
    {
        if (isset($this->listener)) {
            CacheListenerCollector::setListener($this->listener, [
                'className' => $className,
                'method' => $target,
            ]);
        }
        AnnotationCollector::collectMethod($className, $target, static::class, $this);
    }
}
