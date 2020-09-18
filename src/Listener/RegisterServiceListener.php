<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Listener;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\MainWorkerStart;
use Psr\Container\ContainerInterface;
use TheFairLib\Contract\ServiceGovernanceManageInterface;

class RegisterServiceListener implements ListenerInterface
{

    /**
     * @var ServiceGovernanceManageInterface
     */
    private $manage;

    /**
     * @var array
     */
    protected $registeredServices;

    public function __construct(ContainerInterface $container)
    {
        $this->manage = $container->get(ServiceGovernanceManageInterface::class);
    }

    public function listen(): array
    {
        return [
            MainWorkerStart::class,
        ];
    }

    /**
     * 服务注册
     *
     * @param object $event
     */
    public function process(object $event)
    {
        $this->manage->registeredServices();
    }
}
