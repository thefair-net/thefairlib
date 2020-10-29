<?php

declare(strict_types=1);

namespace TheFairLib\Command\Request;

use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TheFairLib\Contract\ServiceGovernanceManageInterface;
use TheFairLib\Library\Http\Request\RequestGenerate;

/**
 * @\Hyperf\Command\Annotation\Command
 * Class DeregisterServerCommand
 * @package TheFairLib\Command\Common
 */
class RequestCommand extends Command
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
        parent::__construct('request');
        $this->setDescription('自动生成请求参数文件');
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
        $this->container->get(RequestGenerate::class)->automaticallyGenerate($input, $output);
        $output->writeln('------------------ success ------------------');
        return 0;
    }
}
