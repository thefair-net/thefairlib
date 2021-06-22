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
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('------------------ start ------------------');
        $path = config('app.service_status_path', '');
        $sleep = config('app.service_stop_sleep', 6);
        $sleep = max(6, $sleep);
        clearstatcache();
        if (!file_exists($path)) {
            file_put_contents($path, '403');
            $i = 1;
            while ($i <= $sleep) {
                $output->writeln(sprintf('------------------ sleep %ds ------------------', $i));
                sleep(1);
                $i++;
            }
        }
        $output->writeln('------------------ success ------------------');
        return 0;
    }
}
