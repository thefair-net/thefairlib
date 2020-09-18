<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Model;

use Hyperf\Database\ConnectionInterface;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Utils\ApplicationContext;

/**
 * @method bool hasTable(string $table)
 * @method array getColumnListing(string $table)
 * @method array getColumnTypeListing(string $table)
 * @method void dropAllTables()
 * @method void dropAllViews()
 * @method array getAllTables()
 * @method array getAllViews()
 * @method bool hasColumn(string $table, string $column)
 * @method bool hasColumns(string $table, array $columns)
 * @method string getColumnType(string $table, string $column)
 * @method void table(string $table, \Closure $callback)
 * @method void create(string $table, \Closure $callback))
 * @method void drop(string $table)
 * @method void dropIfExists(string $table)
 * @method void rename(string $from, string $to)
 * @method bool enableForeignKeyConstraints()
 * @method bool disableForeignKeyConstraints()
 * @method \Hyperf\Database\Connection getConnection()
 * @method \Hyperf\Database\Schema\Builder setConnection(\Hyperf\Database\Connection $connection)
 * @method void blueprintResolver(\Closure $resolver)
 */
class Schema
{
    protected $connection = 'default';


    public function __call($name, $arguments)
    {
        return $this->connection()->getSchemaBuilder()->{$name}(...$arguments);
    }

    /**
     * Create a connection by ConnectionResolver.
     */
    protected function connection(): ConnectionInterface
    {
        $container = ApplicationContext::getContainer();
        $resolver = $container->get(ConnectionResolverInterface::class);
        return $resolver->connection($this->connection);
    }

    public function setPoolName(string $poolName)
    {
        return $this->connection = $poolName;
    }
}
