<?php

declare(strict_types=1);

namespace TheFairLib\Command\Common;

use Hyperf\Server\Server;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TheFairLib\Contract\ServiceGovernanceManageInterface;
use TheFairLib\Library\Logger\Logger;
use TheFairLib\Service\ServiceGovernance\Consul\Manage;

/**
 * @\Hyperf\Command\Annotation\Command
 * Class DeregisterServerCommand
 * @package TheFairLib\Command\Common
 */
class DeregisterServerCommand extends Command
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ServiceGovernanceManageInterface
     */
    private $manage;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->manage = $container->get(ServiceGovernanceManageInterface::class);
        parent::__construct('deregister_consul');
        $this->setDescription('注销 consul 服务节点');
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
        if ($this->manage->deregisterConsul()) {
            $i = 1;
            $num = 3;
            while ($i <= $num) {
                Logger::get()->info(sprintf('service deregister countdown: %ds', ($num + 1) - $i));
                $output->writeln(sprintf('service deregister countdown: %ds', ($num + 1) - $i));
                sleep(1);
                $i++;
            }
        }
    }
}
