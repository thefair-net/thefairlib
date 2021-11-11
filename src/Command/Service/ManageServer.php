<?php

declare(strict_types=1);

namespace TheFairLib\Command\Service;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Nacos\Application;
use Hyperf\Server\Server;
use Hyperf\ServiceGovernance\IPReaderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TheFairLib\Exception\ServiceException;
use TheFairLib\Library\Logger\Logger;
use Throwable;
use Hyperf\Nacos\Exception\RequestException;

class ManageServer
{

    /**
     * @Inject()
     * @var ContainerInterface
     */
    protected $container;

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
     * @throws Throwable
     */
    public function stop(InputInterface $input, OutputInterface $output)
    {
        $this->nodeInterception($output);//下线负载节点，有服务注册，会停止心跳
        $this->serviceGovernanceShutdown($output);//下线注册中心
        $this->connInterception($output);//拦截流量
    }

    /**
     * 启动服务
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws Throwable
     */
    public function start(InputInterface $input, OutputInterface $output)
    {
        if (file_exists($this->getConnPath())) {
            @unlink($this->getConnPath());
        }
        if (file_exists($this->getNodePath())) {
            @unlink($this->getNodePath());
        }
        // 分两步，第一步上线节点 ，第二步上线流量 bin/tf_start.sh
        if ($input->getArgument('register') == 'nacos') {
            $this->serviceGovernanceRegister($output);
        }
    }

    /**
     * 节点流量拦截
     *
     * @param OutputInterface $output
     */
    protected function nodeInterception(OutputInterface $output)
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
     * @param OutputInterface $output
     */
    protected function connInterception(OutputInterface $output)
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
     *
     * @param OutputInterface $output
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Throwable
     */
    public function serviceGovernanceShutdown(OutputInterface $output)
    {
        $config = $this->container->get(ConfigInterface::class);

        if (!$config->get('nacos.service.enable', true)) {
            return;
        }
        if (!$config->get('nacos.service.instance.auto_removed', false)) {
            return;
        }
        $ipReader = $this->container->get(IPReaderInterface::class);
        $ip = $ipReader->read();

        $serviceConfig = $config->get('nacos.service', []);
        $serviceName = $serviceConfig['service_name'];
        $groupName = $serviceConfig['group_name'] ?? null;
        $namespaceId = $serviceConfig['namespace_id'] ?? null;
        $instanceConfig = $serviceConfig['instance'] ?? [];
        $deregisterSleep = config('app.deregister_sleep', 10);
        $ephemeral = in_array($instanceConfig['ephemeral'], [true, 'true'], true) ? 'true' : 'false';
        $cluster = $instanceConfig['cluster'] ?? null;
        $weight = $instanceConfig['weight'] ?? null;
        $metadata = $instanceConfig['metadata'] ?? null;
        Logger::get()->info('service_governance_shutdown:start', [
            'pid' => posix_getpid(),
            'ip' => $ip,
            'service_name' => $serviceName,
        ]);
        $client = $this->container->get(Application::class);
        $ports = $config->get('server.servers', []);
        foreach ($ports as $portServer) {
            $port = (int)$portServer['port'];
            $type = (int)$portServer['type'];
            if (Server::SERVER_BASE != $type) {
                continue;
            }
            try {
                //秒级下线
                $response = $client->instance->update($ip, $port, $serviceName, [
                    'groupName' => $groupName,
                    'namespaceId' => $namespaceId,
                    'ephemeral' => $ephemeral,
                    'clusterName' => $cluster,
                    'weight' => $weight,
                    'metadata' => $metadata,
                    'enabled' => 'false',
                ]);
                // 如果没有下线成功，就需要等待 10s，不然无法做到无损下线
                $statusUpdate = $response->getStatusCode() !== 200 && (string)$response->getBody() != 'ok';
                // 先下线，再注销实例，这里注销实例会有延时
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
                    throw new ServiceException('service_governance_shutdown:' . $response->getBody()->getContents());
                }
                Logger::get()->info('service_governance_shutdown:info', [
                    'code' => $response->getStatusCode(),
                    'msg' => $response->getBody()->getContents(),
                    'pid' => posix_getpid(),
                    'ip' => $ip,
                    'port' => $port,
                    'service_name' => $serviceName,
                    'status_update' => $statusUpdate,
                ]);
                // 如果下线失败，就进入 nacos 主要下线
                while ($statusUpdate && $deregisterSleep > 0) {
                    $output->writeln(sprintf('------------------ service_governance_shutdown sleep %ds ------------------', $deregisterSleep));
                    sleep(1);
                    $deregisterSleep--;
                }
            } catch (Throwable $e) {
                Logger::get()->error('service_governance_shutdown:error', [
                    'error' => formatter($e),
                ]);
                throw $e;
            }

        }
    }

    /**
     * 注册服务
     *
     * @param OutputInterface $output
     * @throws Throwable
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function serviceGovernanceRegister(OutputInterface $output)
    {
        $config = $this->container->get(ConfigInterface::class);

        if (!$config->get('nacos')) {
            return;
        }

        $serviceConfig = $config->get('nacos.service', []);
        if (!$serviceConfig || empty($serviceConfig['enable'])) {
            return;
        }

        $serviceName = $serviceConfig['service_name'];
        $groupName = $serviceConfig['group_name'] ?? null;
        $namespaceId = $serviceConfig['namespace_id'] ?? null;
        $protectThreshold = $serviceConfig['protect_threshold'] ?? null;
        $metadata = $serviceConfig['metadata'] ?? null;
        $selector = $serviceConfig['selector'] ?? null;
        $ipReader = $this->container->get(IPReaderInterface::class);
        $ip = $ipReader->read();
        try {
            $client = $this->container->get(Application::class);
            Logger::get()->info('service_governance_register:start', [
                'pid' => posix_getpid(),
                'ip' => $ip,
                'service_name' => $serviceName,
            ]);
            // Register Service to Nacos.
            $optional = [
                'groupName' => $groupName,
                'namespaceId' => $namespaceId,
                'protectThreshold' => $protectThreshold,
                'metadata' => $metadata,
                'selector' => $selector,
            ];
            // 创建服务
            $response = $client->service->create($serviceName, $optional);
            if ($response->getStatusCode() !== 200 && strpos((string)$response->getBody(), 'already exists') > 0) {
                // 如果存在就更新
                $response = $client->service->update($serviceName, $optional);
                if ($response->getStatusCode() !== 200 || (string)$response->getBody() !== 'ok') {
                    throw new RequestException(sprintf('Failed to update nacos service %s!', $serviceName));
                }
            }
            // 报错就直接异常中断
            if ($response->getStatusCode() !== 200 && (string)$response->getBody() != 'ok') {
                throw new RequestException((string)$response->getBody(), $response->getStatusCode());
            }
            // Register Instance to Nacos.
            $instanceConfig = $serviceConfig['instance'] ?? [];
            $ephemeral = in_array($instanceConfig['ephemeral'], [true, 'true'], true) ? 'true' : 'false';
            $cluster = $instanceConfig['cluster'] ?? null;
            $weight = $instanceConfig['weight'] ?? null;
            $metadata = $instanceConfig['metadata'] ?? null;
            $optional = [
                'groupName' => $groupName,
                'namespaceId' => $namespaceId,
                'ephemeral' => $ephemeral,
            ];

            $optionalData = array_merge($optional, [
                'clusterName' => $cluster,
                'weight' => $weight,
                'metadata' => $metadata,
                'enabled' => 'true',
            ]);
            $ports = $config->get('server.servers', []);
            foreach ($ports as $portServer) {
                $port = (int)$portServer['port'];
                $type = (int)$portServer['type'];
                if (Server::SERVER_BASE != $type) {//有多个端口，只注册TCP端口
                    continue;
                }
                // 核心，注册实例
                $response = $client->instance->register($ip, $port, $serviceName, $optionalData);
                if ($response->getStatusCode() !== 200 || (string)$response->getBody() !== 'ok') {
                    throw new RequestException(sprintf('Failed to create nacos instance %s:%d!', $ip, $port));
                }
                /**
                 * 这一步很重要，为什么要更新？
                 * 1. 在下线时，会先下线流量，再注销实例
                 * 2. 在上线时，需要将 `enabled` 打开，这里的 bool 统一转为 string true
                 **/
                $response = $client->instance->update($ip, $port, $serviceName, $optionalData);
                if ($response->getStatusCode() !== 200 || (string)$response->getBody() !== 'ok') {
                    throw new RequestException(sprintf('Failed to update nacos instance %s:%d!', $ip, $port));
                }
                Logger::get()->info('service_governance_register:info', [
                    'code' => $response->getStatusCode(),
                    'msg' => $response->getBody()->getContents(),
                    'pid' => posix_getpid(),
                    'ip' => $ip,
                    'port' => $port,
                    'service_name' => $serviceName,
                ]);
            }
        } catch (Throwable $exception) {
            Logger::get()->error('service_governance_register:error', [
                'error' => formatter($exception),
            ]);
            throw $exception;
        }
    }
}
