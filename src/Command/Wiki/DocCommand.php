<?php

declare(strict_types=1);

namespace TheFairLib\Command\Wiki;

use Hyperf\Command\Command as HyperfCommand;
use Psr\Container\ContainerInterface;
use Throwable;

/**
 * @\Hyperf\Command\Annotation\Command
 * Class DeregisterServerCommand
 * @package TheFairLib\Command\Common
 */
class DocCommand extends HyperfCommand
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        parent::__construct('doc:wiki');
        $this->setDescription('自动生成 api wiki 文档');
    }

    /**
     * 临时
     *
     * @return int
     * @throws Throwable
     */
    public function handle()
    {
        try {
            $this->output->writeln('------------------ start ------------------');
            $this->container->get(DocumentGenerate::class)->execute($this->input, $this->output);
            $this->output->writeln('------------------ end ------------------');
        } catch (\Throwable $e) {
            $this->output->warning(sprintf('------------------ error %s ------------------', $e->getMessage()));
        }
        return 0;
    }

}
