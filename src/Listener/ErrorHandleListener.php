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
use Hyperf\HttpMessage\Server\Response;
use Hyperf\HttpMessage\Stream\SwooleStream;
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
            if (!preg_match('/cli_set_process_title/', $message)) {
                Logger::get()->debug("mac: " . $message);
            }
            if (error_reporting() & $level) {
                Logger::get()->critical('set_error_handler', [
                    'level' => $level,
                    'msg' => $message,
                    'file' => $file,
                    'line' => $line,
                    'content' => $context,
                ]);
                throw new ErrorException($message, 0, $level, $file, $line);
            }
        });

        set_exception_handler(function (Throwable $e) {
            Logger::get()->critical('set_exception_handler', [
                'msg' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace_string' => $e->getTraceAsString(),
            ]);
        });

        register_shutdown_function(function () {
            $error = error_get_last();
            if ($error === null) {
                return;
            }
            switch ($error['type'] ?? null) {
                case E_ERROR:
                case E_PARSE:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                    $msg = arrayGet($error, 'message');
                    Logger::get()->critical('register_shutdown_function', [
                        'msg' => $msg,
                        'file' => arrayGet($error, 'file'),
                        'line' => arrayGet($error, 'line'),
                    ]);
                    /**
                     * @var ResponseInterface $response
                     */
                    $response = new Response();
                    $response->withStatus(ServerCode::SERVER_ERROR)->withBody(new SwooleStream($msg));
                    break;
            }
        });
    }
}
