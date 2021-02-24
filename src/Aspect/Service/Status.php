<?php
/***************************************************************************
 *
 * Copyright (c) 2021 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file Status.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2021-02-24 10:18:00
 *
 **/


declare(strict_types=1);

namespace TheFairLib\Aspect\Service;

use Hyperf\Contract\ContainerInterface;
use Hyperf\Di\Annotation\Aspect;
use Hyperf\Di\Aop\AbstractAspect;
use Hyperf\Di\Aop\ProceedingJoinPoint;
use TheFairLib\Constants\InfoCode;
use TheFairLib\Constants\ServerCode;
use TheFairLib\Exception\BusinessException;

/**
 * @Aspect
 */
class Status extends AbstractAspect
{
    protected $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    public $classes = [
        'App\Controller\Index::ping',
    ];

    public function process(ProceedingJoinPoint $proceedingJoinPoint)
    {
        $path = BASE_PATH . sprintf('/runtime/service_status');
        if (file_exists($path)) {
            throw new BusinessException(InfoCode::CODE_SERVER_FORBIDDEN, [], [], null, ServerCode::FORBIDDEN);
        }
        return $proceedingJoinPoint->process();
    }
}
