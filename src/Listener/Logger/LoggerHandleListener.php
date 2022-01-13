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
            $rate = (int)config('app.access_logger_rate', 100);
            $status = ($rate > 0 && mt_rand(1, 100) <= $rate);
            if ($event instanceof OnReceive && $status) {
                if ($log = getRpcLogArguments()) {
                    Logger::get()->info('access_logger', $log);
                }
            }
            if ($event instanceof OnRequest && $status) {
                if ($log = getHttpLogArguments()) {
                    Logger::get()->info('access_logger', $log);
                }
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
