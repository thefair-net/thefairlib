<?php
declare(strict_types=1);

/**
 * Log类
 */

namespace TheFairLib\Library\Logger;

use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;

class Logger
{
    /**
     * 日志
     *
     * @return LoggerInterface
     */
    public static function get()
    {
        return ApplicationContext::getContainer()->get(LoggerFactory::class)->get(env('APP_NAME'));
    }
}
