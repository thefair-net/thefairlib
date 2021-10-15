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

namespace Hyperf\JsonRpc\Pool;

use Closure;
use Hyperf\Contract\ConnectionInterface;
use Hyperf\JsonRpc\Exception\ClientException;
use Hyperf\LoadBalancer\Node;
use Hyperf\Pool\Connection as BaseConnection;
use Hyperf\Pool\Exception\ConnectionException;
use Hyperf\Pool\Pool;
use Psr\Container\ContainerInterface;
use Swoole\Coroutine as SwooleCo;
use Swoole\Coroutine\Client as SwooleClient;

/**
 * @method bool|int send($data)
 * @method bool|string recv(float $timeout)
 * @property int $errCode
 * @property string $errMsg
 */
class RpcConnection extends BaseConnection implements ConnectionInterface
{
    /**
     * @var SwooleClient
     */
    protected $connection;

    /**
     * @var array
     */
    protected $config = [
        'node' => null,
        'connect_timeout' => 5.0,
        'settings' => [],
    ];

    /**
     * @throws ConnectionException
     */
    public function __construct(ContainerInterface $container, Pool $pool, array $config)
    {
        parent::__construct($container, $pool);
        $this->config = array_replace($this->config, $config);

        $this->reconnect();
    }

    public function __call($name, $arguments)
    {
        return $this->connection->{$name}(...$arguments);
    }

    public function __get($name)
    {
        return $this->connection->{$name};
    }

    /**
     * @return $this
     * @throws ConnectionException
     */
    public function getActiveConnection(): RpcConnection
    {
        if ($this->check()) {
            return $this;
        }
        if (!$this->reconnect()) {
            throw new ConnectionException('Connection reconnect failed.');
        }
        return $this;
    }

    /**
     * 重新连接
     *
     * @return bool
     * @throws ConnectionException
     */
    public function reconnect(): bool
    {
        if (!$this->config['node'] instanceof Closure) {
            throw new ConnectionException('Node of Connection is invalid.');
        }

        /** @var Node $node */
        $node = value($this->config['node']);
        $host = $node->host;
        $port = $node->port;
        $connectTimeout = $this->config['connect_timeout'];

        $client = new SwooleClient(SWOOLE_SOCK_TCP);
        $client->set($this->config['settings'] ?? []);
        $result = $client->connect($host, $port, $connectTimeout);
        if ($result === false) {
            // Force close and reconnect to server.
            $client->close();
            throw new ClientException('Connect to server failed. ' . $client->errMsg . ' # ' . encode([
                    'host' => $host,
                    'port' => $port,
                    'timeout' => $connectTimeout,
                    'cid' => SwooleCo::getCid(),
                    'pid' => posix_getpid(),
                    'err_rode' => $client->errCode,
                    'file' => __FILE__,
                ]), $client->errCode);
        }

        $this->connection = $client;
        $this->lastUseTime = microtime(true);
        return true;
    }

    public function check(): bool
    {
        $maxIdleTime = $this->pool->getOption()->getMaxIdleTime();
        $now = microtime(true);
        if ($now > $maxIdleTime + $this->lastUseTime) {
            return false;
        }
        if ($this->lastUseTime <= 0) {
            $this->lastUseTime = $now;
        }
        return true;
    }

    public function close(): bool
    {
        $this->lastUseTime = 0.0;
        $this->connection->close();
        return true;
    }

    public function resetLastUseTime(): void
    {
        $this->lastUseTime = 0.0;
    }
}
