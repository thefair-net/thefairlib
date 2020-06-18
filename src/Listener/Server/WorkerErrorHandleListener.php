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

namespace TheFairLib\Listener\Server;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\OnWorkerError;
use TheFairLib\Library\Logger\Logger;

class WorkerErrorHandleListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function listen(): array
    {
        return [
            OnWorkerError::class,
        ];
    }

    public function process(object $event)
    {
        if ($event instanceof OnWorkerError) {
            Logger::get()->error(sprintf(
                'event: %s , server: %s, worker_id: %d',
                OnWorkerError::class,
                $event->server->getLastError(),
                $event->workerId
            ));
        }
    }
}
