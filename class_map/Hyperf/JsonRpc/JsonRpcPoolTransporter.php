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

namespace Hyperf\JsonRpc;

use Hyperf\Contract\PackerInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\JsonRpc\Pool\PoolFactory;
use Hyperf\JsonRpc\Pool\RpcConnection;
use Hyperf\LoadBalancer\LoadBalancerInterface;
use Hyperf\LoadBalancer\Node;
use Hyperf\Pool\Pool;
use Hyperf\Rpc\Contract\DataFormatterInterface;
use Hyperf\Rpc\Contract\TransporterInterface;
use Hyperf\Rpc\Protocol;
use Hyperf\Rpc\ProtocolManager;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;
use Hyperf\Utils\Exception\ExceptionThrower;
use Psr\Container\ContainerInterface;
use TheFairLib\Constants\InfoCode;
use TheFairLib\Exception\Service\RetryException;
use TheFairLib\Exception\ServiceException;
use Throwable;

class JsonRpcPoolTransporter implements TransporterInterface
{
    use RecvTrait;

    /**
     * @var PoolFactory
     */
    protected $factory;

    /**
     * @var null|LoadBalancerInterface
     */
    private $loadBalancer;

    /**
     * If $loadBalancer is null, will select a node in $nodes to request,
     * otherwise, use the nodes in $loadBalancer.
     *
     * @var Node[]
     */
    private $nodes = [];

    /**
     * @var float
     */
    private $connectTimeout = 5;

    /**
     * @var float
     */
    private $recvTimeout = 5;

    /**
     * @var int
     */
    private $retryCount = 0;

    /**
     * @var int ms
     */
    private $retryInterval = 0;

    /**
     * The protocol of the target service, this protocol name
     * needs to register into \Hyperf\Rpc\ProtocolManager.
     *
     * @var string
     */
    protected $protocol = 'jsonrpc-tcp-length-check';

    /**
     * @var PackerInterface
     */
    protected $packer;
    /**
     * @var DataFormatterInterface
     */
    protected $dataFormatter;

    private $config = [
        'connect_timeout' => 5.0,
        'settings' => [],
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 32,
            'connect_timeout' => 10.0,
            'wait_timeout' => 3.0,
            'heartbeat' => -1,
            'max_idle_time' => 60.0,
        ],
        'recv_timeout' => 5.0,
        'retry_count' => 2,
        'retry_interval' => 100,
    ];

    public function __construct(PoolFactory $factory, array $config = [], ContainerInterface $container)
    {
        $this->factory = $factory;
        $this->config = array_replace_recursive($this->config, $config);

        $this->recvTimeout = $this->config['recv_timeout'] ?? 5.0;
        $this->connectTimeout = $this->config['connect_timeout'] ?? 5.0;
        $this->retryCount = $this->config['retry_count'] ?? 2;
        $this->retryInterval = $this->config['retry_interval'] ?? 100;

        $protocol = new Protocol($container, $container->get(ProtocolManager::class), $this->protocol, [
            'settings' => [
                // 根据协议不同，区分配置
                'open_length_check' => true,
                'package_length_type' => 'N',
                'package_length_offset' => 0,
                'package_body_offset' => 4,
                'package_max_length' => 1024 * 1024 * 2,
            ],
        ]);
        $this->packer = $protocol->getPacker();
        $this->dataFormatter = $protocol->getDataFormatter();
    }

    /**
     * 发送
     *
     * @param string $data
     * @return mixed
     * @throws Throwable
     */
    public function send(string $data)
    {
        $result = retry($this->retryCount, function ($attempts) use ($data) {
            try {
                $force = $attempts > 1;//重试大于1，就强制新建 pool
                $client = $this->getConnection($force);
                if ($client->send($data) === false) {
                    throw new ServiceException('Send data failed. ' . $client->errMsg, [
                        'error' => $client->errCode,
                    ]);
                }
                $result = $this->recvAndCheck($client, $this->recvTimeout);
                $response = $this->packer->unpack($result);
                if (is_array($response) && InfoCode::CODE_SERVER_HTTP_NOT_FOUND == arrayGet($response, 'result.code', 0)) {
                    $msg = arrayGet($response, 'result.message.text', '');
                    $ret = arrayGet($response, 'result.result', []);
                    throw new RetryException($msg ?? '', $ret ?? [], InfoCode::CODE_SERVER_HTTP_NOT_FOUND);
                }
                return $result;
            } catch (Throwable $throwable) {
                if (isset($client)) {
                    $client->close();
                    $class = spl_object_hash($this) . '.Connection';
                    Context::set($class, null);
                }
                throw $throwable;
            }
        }, $this->retryInterval);
        if ($result instanceof ExceptionThrower) {
            throw $result->getThrowable();
        }
        return $result;
    }

    public function recv()
    {
        $client = $this->getConnection();

        return $this->recvAndCheck($client, $this->recvTimeout);
    }

    /**
     * Get RpcConnection from Context.
     */
    public function getConnection(bool $force = false): RpcConnection
    {
        $class = spl_object_hash($this) . '.Connection';
        /** @var RpcConnection $connection */
        $connection = Context::get($class);
        if (isset($connection)) {
            try {
                if (!$connection->check()) {
                    // Try to reconnect the target server.
                    $connection->reconnect();
                }
                return $connection;
            } catch (Throwable $exception) {
                $this->log($exception);
            }
        }
        $connection = $this->getPool()->get();
        if ($force) {
            $connection->reconnect();//
        }
        defer(function () use ($connection) {
            $connection->release();
        });

        return Context::set($class, $connection->getConnection());
    }

    public function getPool(): Pool
    {
        $name = spl_object_hash($this) . '.Pool';
        $config = [
            'connect_timeout' => $this->config['connect_timeout'],
            'settings' => $this->config['settings'],
            'pool' => $this->config['pool'],
            'node' => function () {
                return $this->getNode();
            },
        ];

        return $this->factory->getPool($name, $config);
    }

    public function getLoadBalancer(): ?LoadBalancerInterface
    {
        return $this->loadBalancer;
    }

    public function setLoadBalancer(LoadBalancerInterface $loadBalancer): TransporterInterface
    {
        $this->loadBalancer = $loadBalancer;
        return $this;
    }

    /**
     * @param \Hyperf\LoadBalancer\Node[] $nodes
     * @return JsonRpcPoolTransporter
     */
    public function setNodes(array $nodes): self
    {
        $this->nodes = $nodes;
        return $this;
    }

    /**
     * @return \Hyperf\LoadBalancer\Node[]
     */
    public function getNodes(): array
    {
        return $this->nodes;
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * If the load balancer is exists, then the node will select by the load balancer,
     * otherwise will get a random node.
     */
    private function getNode(): Node
    {
        if ($this->loadBalancer instanceof LoadBalancerInterface) {
            return $this->loadBalancer->select();
        }
        return $this->nodes[array_rand($this->nodes)];
    }

    private function log($message)
    {
        $container = ApplicationContext::getContainer();
        if ($container->has(StdoutLoggerInterface::class) && $logger = $container->get(StdoutLoggerInterface::class)) {
            $logger->error((string)$message);
        }
    }
}
