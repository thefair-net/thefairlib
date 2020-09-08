<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file Manage.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-09-08 14:26:00
 *
 **/

namespace TheFairLib\Service\ServiceGovernance\Consul;

use Hyperf\Consul\Exception\ServerException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Framework\Event\MainWorkerStart;
use Hyperf\Server\Server;
use Hyperf\ServiceGovernance\Register\ConsulAgent;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use TheFairLib\Contract\ServiceGovernanceManageInterface;
use TheFairLib\Library\Logger\Logger;

class Manage implements ServiceGovernanceManageInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ConsulAgent
     */
    protected $consulAgent;

    /**
     * @var ConfigInterface
     */
    protected $config;

    /**
     * @var array
     */
    protected $registeredServices;

    /**
     * @var array
     */
    protected $defaultLoggerContext
        = [
            'component' => 'service-governance',
        ];

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->consulAgent = $container->get(ConsulAgent::class);
        $this->config = $container->get(ConfigInterface::class);
    }

    public function deregisterConsul(): bool
    {
        $serverConfig = $this->config->get('consul', []);
        if (!$serverConfig) {
            throw new InvalidArgumentException('At least one server should be defined.');
        }
        if (arrayGet($serverConfig, 'enable')) {
            $servers = $this->getServers();
            foreach ($servers as $name => $server) {
                [$address, $port, $type] = $server;
                switch ($type) {
                    case Server::SERVER_BASE:
                        $protocol = 'jsonrpc';
                        $serviceId = $this->getServiceId(env('APP_NAME'), $address, (int)$port, $protocol);
                        if ($this->deregisterService($serviceId)) {
                            return true;
                        }
                        break;
                }
            }
        }
        return false;
    }

    protected function deregisterService($serviceId)
    {
        if (!$serviceId) {
            return false;
        }
        $response = $this->consulAgent->deregisterService($serviceId);
        if ($response->getStatusCode() !== 200) {
            return false;
        }
        Logger::get()->info(sprintf('service deregister success, service_id: %s', $serviceId));
        return true;
    }

    /**
     * @return array
     */
    protected function getServers(): array
    {
        $result = [];
        $servers = $this->config->get('server.servers', []);
        foreach ($servers as $server) {
            if (!isset($server['name'], $server['host'], $server['port'])) {
                continue;
            }
            if (!$server['name']) {
                throw new \InvalidArgumentException('Invalid server name');
            }
            $host = $server['host'];
            if (in_array($host, ['0.0.0.0', 'localhost'])) {
                $host = $this->getInternalIp();
            }
            if (!filter_var($host, FILTER_VALIDATE_IP)) {
                throw new \InvalidArgumentException(sprintf('Invalid host %s', $host));
            }
            $port = $server['port'];
            if (!is_numeric($port) || ($port < 0 || $port > 65535)) {
                throw new \InvalidArgumentException(sprintf('Invalid port %s', $port));
            }
            $type = $server['type'];
            if (!is_numeric($port) || !in_array($type, [Server::SERVER_BASE, Server::SERVER_HTTP])) {
                throw new \InvalidArgumentException(sprintf('Invalid type %s', $type));
            }
            $port = (int)$port;
            $result[$server['name']] = [$host, $port, $type];
        }
        return $result;
    }

    /**
     * 获得 IP
     *
     * @return string
     */
    protected function getInternalIp(): string
    {
        $ips = swoole_get_local_ip();
        if (is_array($ips)) {
            return current($ips);
        }
        $ip = gethostbyname(gethostname());
        if (is_string($ip)) {
            return $ip;
        }
        throw new \RuntimeException('Can not get the internal IP.');
    }

    /**
     * 获得要注册的服务ID
     *
     * @param string $name
     * @param string $address
     * @param int $port
     * @param string $protocol
     * @return string
     */
    protected function getServiceId(string $name, string $address, int $port, string $protocol): string
    {
        $response = $this->consulAgent->services();
        if ($response->getStatusCode() !== 200) {
            Logger::get()->error(sprintf('Service %s register to the consul failed.', $name));
            return '';
        }
        $services = $response->json();
        $glue = ',';
        $tag = implode($glue, [$name, $address, $port, $protocol]);
        foreach ($services as $serviceId => $service) {
            if (!isset($service['Service'], $service['Address'], $service['Port'], $service['Meta']['Protocol'])) {
                continue;
            }
            $currentTag = implode($glue, [
                $service['Service'],
                $service['Address'],
                $service['Port'],
                $service['Meta']['Protocol'],
            ]);
            if ($currentTag === $tag) {
                return arrayGet($service, 'ID');
            }
        }
        return '';
    }

    protected function publishToConsul(string $address, int $port, array $service, string $serviceName, string $path)
    {
        Logger::get()->debug(sprintf('Service %s[%s] is registering to the consul.', $serviceName, $path), $this->defaultLoggerContext);
        if ($this->isRegistered($serviceName, $address, $port, $service['protocol'])) {
            Logger::get()->info(sprintf('Service %s[%s] has been already registered to the consul.', $serviceName, $path), $this->defaultLoggerContext);
            return;
        }
        if (isset($service['id']) && $service['id']) {
            $nextId = $service['id'];
        } else {
            $nextId = $this->generateId($this->getLastServiceId($serviceName));
        }
        $requestBody = [
            'Name' => $serviceName,
            'ID' => $nextId,
            'Address' => $address,
            'Port' => $port,
            'Meta' => [
                'Protocol' => $service['protocol'],
            ],
        ];
        rd_debug($requestBody);
        if ($service['protocol'] === 'jsonrpc-http') {
            $requestBody['Check'] = [
                'DeregisterCriticalServiceAfter' => '90m',
                'HTTP' => "http://{$address}:{$port}/",
                'Interval' => '1s',
            ];
        }
        if (in_array($service['protocol'], ['jsonrpc', 'jsonrpc-tcp-length-check'], true)) {
            $requestBody['Check'] = [
                'DeregisterCriticalServiceAfter' => '90m',
                'TCP' => "{$address}:{$port}",
                'Interval' => '1s',
            ];
        }
        $response = $this->consulAgent->registerService($requestBody);
        if ($response->getStatusCode() === 200) {
            $this->registeredServices[$serviceName][$service['protocol']][$address][$port] = true;
            Logger::get()->info(sprintf('Service %s[%s]:%s register to the consul successfully.', $serviceName, $path, $nextId), $this->defaultLoggerContext);
        } else {
            Logger::get()->warning(sprintf('Service %s register to the consul failed.', $serviceName), $this->defaultLoggerContext);
        }
    }

    protected function generateId(string $name)
    {
        $exploded = explode('-', $name);
        $length = count($exploded);
        $end = -1;
        if ($length > 1 && is_numeric($exploded[$length - 1])) {
            $end = $exploded[$length - 1];
            unset($exploded[$length - 1]);
        }
        $end = intval($end);
        ++$end;
        $exploded[] = $end;
        return implode('-', $exploded);
    }

    protected function getLastServiceId(string $name)
    {
        $maxId = -1;
        $lastService = $name;
        $services = $this->consulAgent->services()->json();
        foreach ($services ?? [] as $id => $service) {
            if (isset($service['Service']) && $service['Service'] === $name) {
                $exploded = explode('-', (string)$id);
                $length = count($exploded);
                if ($length > 1 && is_numeric($exploded[$length - 1]) && $maxId < $exploded[$length - 1]) {
                    $maxId = $exploded[$length - 1];
                    $lastService = $service;
                }
            }
        }
        return $lastService['ID'] ?? $name;
    }

    protected function isRegistered(string $name, string $address, int $port, string $protocol): bool
    {
        if (isset($this->registeredServices[$name][$protocol][$address][$port])) {
            return true;
        }
        $response = $this->consulAgent->services();
        if ($response->getStatusCode() !== 200) {
            Logger::get()->warning(sprintf('Service %s register to the consul failed.', $name), $this->defaultLoggerContext);
            return false;
        }
        $services = $response->json();
        $glue = ',';
        $tag = implode($glue, [$name, $address, $port, $protocol]);
        foreach ($services as $serviceId => $service) {
            if (!isset($service['Service'], $service['Address'], $service['Port'], $service['Meta']['Protocol'])) {
                continue;
            }
            $currentTag = implode($glue, [
                $service['Service'],
                $service['Address'],
                $service['Port'],
                $service['Meta']['Protocol'],
            ]);
            if ($currentTag === $tag) {
                $this->registeredServices[$name][$protocol][$address][$port] = true;
                return true;
            }
        }
        return false;
    }

    /**
     * 服务注册
     */
    public function registeredServices()
    {
        /**
         * @var MainWorkerStart $event
         */
        $this->registeredServices = [];
        $continue = true;
        while ($continue) {
            try {
                $enable = $this->config->get('consul.enable', false);
                if ($enable) {
                    $servers = $this->getServers();
                    foreach ($servers as $name => $server) {
                        [$address, $port, $type] = $server;
                        switch ($type) {
                            case Server::SERVER_BASE:
                                $protocol = 'jsonrpc';
                                $this->publishToConsul($address, (int)$port, [
                                    'server' => $name,
                                    'protocol' => $protocol,
                                    'publishTo' => 'consul',
                                ], env('APP_NAME'), $protocol);
                                break;
                        }
                    }
                }
                $continue = false;
            } catch (ServerException $throwable) {
                if (strpos($throwable->getMessage(), 'Connection failed') !== false) {
                    Logger::get()->warning('Cannot register service, connection of service center failed, re-register after 10 seconds.');
                    sleep(10);
                } else {
                    throw $throwable;
                }
            }
        }
    }
}
