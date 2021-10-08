<?php

declare(strict_types=1);

namespace TheFairLib\Command\Service;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ManageServer
{
    /**
     * slb 负载状态检测
     *
     * @return bool
     */
    public function getStatus(): bool
    {
        return config('app.service_status', true);
    }

    public function getNodePath(): string
    {
        return $this->getPath('node');
    }

    public function getConnPath(): string
    {
        return $this->getPath('conn');
    }

    /**
     * 路径
     *
     * @param string $type
     * @return string
     */
    protected function getPath(string $type): string
    {
        $path = config('app.service_status_path', '');
        if (!empty($path)) {
            $path = sprintf("%s.%s", $path, $type);
        }
        return $path;
    }

    /**
     * 停止服务
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function stop(InputInterface $input, OutputInterface $output)
    {
        $this->nodeInterception($input, $output);
        $this->connInterception($input, $output);
    }

    /**
     * 启动服务
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    public function start(InputInterface $input, OutputInterface $output)
    {
        if (file_exists($this->getConnPath())) {
            @unlink($this->getConnPath());
        }
        if (file_exists($this->getNodePath())) {
            @unlink($this->getNodePath());
        }
    }

    /**
     * 节点流量拦截
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function nodeInterception(InputInterface $input, OutputInterface $output)
    {
        $sleep = config('app.service_node_sleep', 5);
        $sleep = max(5, $sleep);
        if (!file_exists($this->getNodePath())) {
            file_put_contents($this->getNodePath(), '403');
            $i = 1;
            while ($i <= $sleep) {
                $output->writeln(sprintf('------------------ node sleep %ds ------------------', $i));
                sleep(1);
                $i++;
            }
        }
    }

    /**
     * 连接流量拦截
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function connInterception(InputInterface $input, OutputInterface $output)
    {
        $sleep = config('app.service_conn_sleep', 5);
        $sleep = max(5, $sleep);
        if (!file_exists($this->getConnPath())) {
            file_put_contents($this->getConnPath(), '404');
            $i = 1;
            while ($i <= $sleep) {
                $output->writeln(sprintf('------------------ conn sleep %ds ------------------', $i));
                sleep(1);
                $i++;
            }
        }
    }
}
