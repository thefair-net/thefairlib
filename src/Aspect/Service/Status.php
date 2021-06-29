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
use TheFairLib\Command\Service\ManageServer;
use TheFairLib\Constants\InfoCode;
use TheFairLib\Constants\ServerCode;
use TheFairLib\Exception\Service\TermException;

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
        $path = $this->container->get(ManageServer::class)->getNodePath();
        if (file_exists($path)) {
            throw new TermException(InfoCode::CODE_SERVER_HTTP_NOT_FOUND, [], [], null, ServerCode::HTTP_NOT_FOUND);
        }
        return $proceedingJoinPoint->process();
    }
}
