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

namespace TheFairLib\Exception\Handler\Rpc;

use Hyperf\Di\Annotation\Inject;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\Utils\Context;
use Psr\Http\Message\ServerRequestInterface;
use TheFairLib\Constants\InfoCode;
use TheFairLib\Constants\ServerCode;
use TheFairLib\Contract\ResponseBuilderInterface;
use TheFairLib\Exception\Handler\ExceptionHandler;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use TheFairLib\Library\Logger\Logger;
use Throwable;

class RpcAppExceptionHandler extends ExceptionHandler
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @Inject
     * @var ResponseBuilderInterface
     */
    protected $responseBuilder;

    /**
     * @var FormatterInterface
     */
    protected $formatter;

    public function __construct(StdoutLoggerInterface $logger, FormatterInterface $formatter)
    {
        $this->logger = $logger;
        $this->formatter = $formatter;
    }

    /**
     * @param Throwable $throwable
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        Logger::get()->error(sprintf('error exception:%s', get_class($throwable)), [
                'msg' => $throwable->getMessage(),
                'line' => $throwable->getLine(),
                'file' => $throwable->getFile(),
                'code' => $throwable->getCode(),
                'trace_string' => $throwable->getTraceAsString(),
            ]
        );

        $result = $this->serviceResponse->showError(
            $throwable->getMessage(),
            ['data' => $response->getBody(), 'exception' => get_class($throwable)],
            $throwable->getCode() > 0 ? $throwable->getCode() : InfoCode::CODE_ERROR
        );

        return $this->responseBuilder->buildResponse(
            Context::get(ServerRequestInterface::class),
            $result
        );
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
