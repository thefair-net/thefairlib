<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Listener;

use TheFairLib\Server\Security\DbQuery;
use Hyperf\Database\Events\QueryExecuted;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Str;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

/**
 * @Listener
 */
class DbQueryExecutedListener implements ListenerInterface
{

    /**
     * @var DbQuery
     */
    private $dbQuery;

    public function __construct(DbQuery $dbQuery)
    {
        $this->dbQuery = $dbQuery;
    }

    public function listen(): array
    {
        return [
            QueryExecuted::class,
        ];
    }

    /**
     * @param object QueryExecuted $event
     */
    public function process(object $event)
    {
        if ($event instanceof QueryExecuted) {
            $sql = $event->sql;
            if (!Arr::isAssoc($event->bindings)) {
                foreach ($event->bindings as $key => $value) {
                    $sql = Str::replaceFirst('?', "'{$value}'", $sql);
                }
            }
            rd_debug([
                $sql, $event->bindings
            ]);
            $this->dbQuery->checkQuery($sql, $event->bindings, $event->connectionName);
        }
    }
}
