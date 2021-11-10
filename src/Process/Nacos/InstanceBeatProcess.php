<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Process\Nacos;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Nacos\Application;
use Hyperf\Process\AbstractProcess;
use Hyperf\Process\Annotation\Process;
use Hyperf\Process\ProcessManager;
use Hyperf\Server\Server;
use Hyperf\ServiceGovernance\IPReaderInterface;
use TheFairLib\Command\Service\ManageServer;
use TheFairLib\Library\Logger\Logger;

/**
 *
 * Class InstanceBeatProcess
 * @package TheFairLib\Process\Nacos
 */
class InstanceBeatProcess extends AbstractProcess
{
    /**
     * @var string
     */
    public $name = 'nacos-heartbeat';

    public function handle(): void
    {
        $config = $this->container->get(ConfigInterface::class);
        $client = $this->container->get(Application::class);

        $serviceConfig = $config->get('nacos.service', []);
        $serviceName = $serviceConfig['service_name'];
        $namespaceId = $serviceConfig['namespace_id'];
        $groupName = $serviceConfig['group_name'] ?? null;
        $instanceConfig = $serviceConfig['instance'] ?? [];
        $ephemeral = $instanceConfig['ephemeral'] ?? null;
        $cluster = $instanceConfig['cluster'] ?? null;
        $weight = $instanceConfig['weight'] ?? null;
        $ipReader = $this->container->get(IPReaderInterface::class);
        $ip = $ipReader->read();

        while (ProcessManager::isRunning()) {
            $heartbeat = $config->get('nacos.service.instance.heartbeat', 5);
            sleep($heartbeat ?: 5);

            $ports = $config->get('server.servers', []);
            foreach ($ports as $portServer) {
                $port = (int)$portServer['port'];
                $type = (int)$portServer['type'];
                if (Server::SERVER_BASE != $type) {
                    continue;
                }
                //下线节点
                if (file_exists($this->container->get(ManageServer::class)->getNodePath())) {
                    continue;
                }
                $response = $client->instance->beat(
                    $serviceName,
                    [
                        'ip' => $ip,
                        'port' => $port,
                        'serviceName' => $groupName . '@@' . $serviceName,
                        'cluster' => $cluster,
                        'weight' => $weight,
                    ],
                    $groupName,
                    $namespaceId,
                    $ephemeral
                );
                if ($response->getStatusCode() !== 200) {
                    Logger::get()->error('nacos-heartbeat:error', [
                        'ip' => $ip,
                        'port' => $port,
                        'msg' => $response->getBody()->getContents(),
                    ]);
                }
            }
        }
    }

    public function isEnable($server): bool
    {
        $config = $this->container->get(ConfigInterface::class);
        return $config->get('nacos.service.enable', true) && $config->get('nacos.service.instance.heartbeat', 0);
    }
}
