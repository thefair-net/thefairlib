<?php
/***************************************************************************
 *
 * Copyright (c) 2021 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file TermSignalHandler.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2021-01-28 18:36:00
 *
 **/

namespace TheFairLib\Listener\Server;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Process\ProcessManager;
use Hyperf\Signal\Annotation\Signal;
use Hyperf\Signal\SignalHandlerInterface;
use Psr\Container\ContainerInterface;
use Swoole\Server;
use TheFairLib\Library\Logger\Logger;
use Throwable;

/**
 * @Signal
 */
class TermSignalHandler implements SignalHandlerInterface
{
    protected $processed = false;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ConfigInterface
     */
    protected $config;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->config = $container->get(ConfigInterface::class);
    }

    public function listen(): array
    {
        return [
            [SignalHandlerInterface::WORKER, SIGTERM],
        ];
    }

    public function handle(int $signal): void
    {
        try {
            ProcessManager::setRunning(false);
            if ($signal !== SIGINT) {
                $time = $this->config->get('server.settings.max_wait_time', 1);
                sleep($time);
            }
        } catch (Throwable $e) {
            Logger::get()->critical('shutdown_event', [
                'e' => $e->getMessage(),
            ]);
        } finally {
            Logger::get()->warning('term_worker_stop', [
                'worker_pid' => posix_getpid(),
            ]);
            $this->container->get(Server::class)->stop();
        }
    }

}
