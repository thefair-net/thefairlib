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

use Hyperf\AsyncQueue\Event\AfterHandle;
use Hyperf\AsyncQueue\Event\BeforeHandle;
use Hyperf\AsyncQueue\Event\Event;
use Hyperf\AsyncQueue\Event\FailedHandle;
use Hyperf\AsyncQueue\Event\RetryHandle;
use Hyperf\Event\Annotation\Listener;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\Logger\LoggerFactory;
use Psr\Log\LoggerInterface;
use TheFairLib\Library\Logger\Logger;

/**
 * @Listener
 */
class QueueHandleListener implements ListenerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FormatterInterface
     */
    protected $formatter;

    public function __construct(LoggerFactory $loggerFactory, FormatterInterface $formatter)
    {
        $this->formatter = $formatter;
    }

    public function listen(): array
    {
        return [
            AfterHandle::class,
            BeforeHandle::class,
            FailedHandle::class,
            RetryHandle::class,
        ];
    }

    public function process(object $event)
    {
        if ($event instanceof Event && $event->message->job()) {
            $job = $event->message->job();
            $jobClass = get_class($job);
            $date = date('Y-m-d H:i:s');

            switch (true) {
                case $event instanceof BeforeHandle:
                    Logger::get()->info(sprintf('[%s] Processing %s.', $date, $jobClass));
                    break;
                case $event instanceof AfterHandle:
                    Logger::get()->info(sprintf('[%s] Processed %s.', $date, $jobClass));
                    break;
                case $event instanceof FailedHandle:
                    Logger::get()->error(sprintf('[%s] Failed %s.', $date, $jobClass));
                    Logger::get()->error($this->formatter->format($event->getThrowable()));
                    break;
                case $event instanceof RetryHandle:
                    Logger::get()->warning(sprintf('[%s] Retried %s.', $date, $jobClass));
                    break;
            }
        }
    }
}
