<?php

declare(strict_types=1);

namespace TheFairLib\Command\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;

/**
 * @\Hyperf\Command\Annotation\Command
 * Class DeregisterServerCommand
 * @package TheFairLib\Command\Common
 */
class ManageStopCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    private $container;


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('manage:stop');
        $this->setDescription('slb 负载状态检测 stop');
    }

    /**
     * 注销服务
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        run(function () use ($input, $output) {
            $output->writeln('------------------ start ------------------');
            $this->container->get(ManageServer::class)->stop($input, $output);
            $output->writeln('------------------ success ------------------');
        });
        return 0;
    }
}
