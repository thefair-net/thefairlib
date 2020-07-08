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

namespace TheFairLib\Listener\Logger;

use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\OnReceive;
use TheFairLib\Event\OnRequest;
use TheFairLib\Library\Logger\Logger;
use Throwable;

class LoggerHandleListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function listen(): array
    {
        return [
            OnReceive::class,
            OnRequest::class,
        ];
    }

    /**
     * @param object $event
     */
    public function process(object $event)
    {
        try {
            if ($event instanceof OnReceive) {
                Logger::get()->info(sprintf("access_logger"), getRpcLogArguments());
            }
            if ($event instanceof OnRequest) {
                Logger::get()->info(sprintf("access_logger"), getHttpLogArguments());
            }
        } catch (Throwable $e) {
            Logger::get()->error(sprintf(
                "write access logger error, %s,code:%d,file:%s,line:%d",
                $e->getMessage(),
                $e->getCode(),
                $e->getFile(),
                $e->getLine()
            ));
        }
    }
}
