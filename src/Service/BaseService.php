<?php

declare(strict_types=1);

namespace TheFairLib\Service;

use TheFairLib\Contract\LockInterface;
use Hyperf\Di\Annotation\Inject;
use Psr\EventDispatcher\EventDispatcherInterface;

abstract class BaseService
{

    /**
     * 锁，目前是使用 redis 现实.
     *
     * @Inject
     * @var LockInterface
     */
    protected $lock;
}
