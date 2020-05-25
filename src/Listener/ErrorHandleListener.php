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

use ErrorException;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Utils\Coroutine;
use TheFairLib\Constants\ServerCode;
use TheFairLib\Library\Logger\Logger;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ErrorHandleListener implements ListenerInterface
{
    /**
     * {@inheritdoc}
     */
    public function listen(): array
    {
        return [
            BootApplication::class,
        ];
    }

    public function process(object $event)
    {
        set_error_handler(function ($level, $message, $file = '', $line = 0, $context = []) {
            $errors = [
                'level' => $level,
                'message' => $message,
                'file' => $file,
                'line' => $line,
                'args' => $_SERVER['argv'],
            ];
            if (!preg_match('/cli_set_process_title/', $message)) {
                Logger::get()->debug("mac: " . $message);
            }
            if (error_reporting() & $level) {
                Logger::get()->error(encode($errors));
                throw new ErrorException($message, 0, $level, $file, $line);
            }
        });

        set_exception_handler(function (Throwable $e) {
            $errors = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'string' => $e->getTraceAsString(),
                'args' => implode('::', $_SERVER['argv']),
            ];
            rd_debug([$errors, __CLASS__, posix_getpid()]);

            Logger::get()->error(encode($errors));
            throw $e;
        });
        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error === null) {
                return;
            }
            rd_debug([$error, __CLASS__, posix_getpid()]);
            switch ($error['type'] ?? null) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                    // log or send:
                    // error_log($message);get
                    // $server->send($fd, $error['message']);
                    /**
                     * @var ResponseInterface $response
                     */
                    $response = new \Hyperf\HttpMessage\Server\Response();
                    $error['debug'] = [
                        'pid' => posix_getpid(),
                        'cid' => Coroutine::id(),
                        'c_pid' => Coroutine::parentId(),
                    ];
                    Logger::get()->error(encode($error));
                    $response->withStatus(ServerCode::SERVER_ERROR)->withBody(new SwooleStream($error['message']))->send(true);
                    break;
            }
        });
    }
}
