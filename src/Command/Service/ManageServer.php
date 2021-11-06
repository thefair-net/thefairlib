<?php

declare(strict_types=1);

namespace TheFairLib\Command\Service;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Nacos\Application;
use Hyperf\Server\Server;
use Hyperf\ServiceGovernance\IPReaderInterface;
use Hyperf\Utils\ApplicationContext;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TheFairLib\Library\Logger\Logger;
use Throwable;

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
        $this->nodeInterception($input, $output);//下线负载节点
        $this->serviceGovernanceShutdown($output);//下线注册中心
        $this->connInterception($input, $output);//拦截流量
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
        $sleep = max(5, 25);
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

    /**
     * 注销服务
     */
    public function serviceGovernanceShutdown(OutputInterface $output)
    {
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);

        if (!$config->get('nacos.service.enable', true)) {
            return;
        }
        if (!$config->get('nacos.service.instance.auto_removed', false)) {
            return;
        }

        $serviceConfig = $config->get('nacos.service', []);
        $serviceName = $serviceConfig['service_name'];
        $groupName = $serviceConfig['group_name'] ?? null;
        $namespaceId = $serviceConfig['namespace_id'] ?? null;
        $instanceConfig = $serviceConfig['instance'] ?? [];
        $deregisterSleep = $serviceConfig['deregister_sleep'] ?? 10;
        $ephemeral = $instanceConfig['ephemeral'] ?? null;
        $cluster = $instanceConfig['cluster'] ?? null;
        $ipReader = ApplicationContext::getContainer()->get(IPReaderInterface::class);
        $ip = $ipReader->read();
        $client = ApplicationContext::getContainer()->get(Application::class);
        $ports = $config->get('server.servers', []);
        foreach ($ports as $portServer) {
            $port = (int)$portServer['port'];
            $type = (int)$portServer['type'];
            if (Server::SERVER_BASE != $type) {
                continue;
            }
            try {
                $response = $client->instance->delete($serviceName, $groupName, $ip, $port, [
                    'clusterName' => $cluster,
                    'namespaceId' => $namespaceId,
                    'ephemeral' => $ephemeral,
                ]);

                if ($response->getStatusCode() !== 200) {
                    Logger::get()->error('service_governance_shutdown:error', [
                        'code' => $response->getStatusCode(),
                        'msg' => $response->getBody()->getContents(),
                    ]);
                }
                Logger::get()->info('service_governance_shutdown:info', [
                    'code' => $response->getStatusCode(),
                    'msg' => $response->getBody()->getContents(),
                    'pid' => posix_getpid(),
                    'ip' => $ip,
                    'port' => $port,
                    'service_name' => $serviceName,
                ]);
                while ($deregisterSleep > 0) {
                    $output->writeln(sprintf('------------------ conn sleep %ds ------------------', $deregisterSleep));
                    sleep(1);
                    $deregisterSleep--;
                }
            } catch (Throwable $e) {
                Logger::get()->error('service_governance_shutdown:error', [
                    'error' => formatter($e),
                ]);
            }

        }
    }
}
