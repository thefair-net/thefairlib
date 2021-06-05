<?php

declare(strict_types=1);

namespace TheFairLib\Command\Service;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @\Hyperf\Command\Annotation\Command
 * Class DeregisterServerCommand
 * @package TheFairLib\Command\Common
 */
class ManageStartCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    private $container;


    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('manage:start');
        $this->setDescription('slb 负载状态检测 start');
    }

    /**
     * 注销服务
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('------------------ start ------------------');
        $path = config('app.service_status_path', '');
        clearstatcache();
        if (file_exists($path)) {
            @unlink($path);
        }
        $output->writeln('------------------ success ------------------');
        return 0;
    }
}
