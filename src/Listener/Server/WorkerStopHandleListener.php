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
use Hyperf\Framework\Event\OnWorkerStop;
use TheFairLib\Library\Logger\Logger;

class WorkerStopHandleListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function listen(): array
    {
        return [
            OnWorkerStop::class,
        ];
    }

    public function process(object $event)
    {
        if ($event instanceof OnWorkerStop) {
            Logger::get()->warning(sprintf(
                'event: %s , worker_id: %d',
                OnWorkerStop::class,
                $event->workerId
            ));
        }
    }
}
