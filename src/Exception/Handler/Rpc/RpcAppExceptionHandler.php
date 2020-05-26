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

use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use TheFairLib\Constants\InfoCode;
use TheFairLib\Constants\ServerCode;
use TheFairLib\Exception\Handler\ExceptionHandler;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class RpcAppExceptionHandler extends ExceptionHandler
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

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

        $this->logger->error(sprintf('%s in %s code %s[%s]', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile(), $throwable->getCode()));
        $this->logger->error($throwable->getTraceAsString());

        $result = $this->serviceResponse->showError($throwable->getMessage(), ['data' => $response->getBody(), 'exception' => get_class($throwable)], $throwable->getCode() > 0 ? $throwable->getCode() : InfoCode::CODE_ERROR);
        rd_debug([$result, __FILE__, __LINE__, get_class($response)]);
        $this->logger->warning($this->formatter->format($throwable));


        return $response->withStatus(ServerCode::OK)
            ->withAddedHeader('content-type', 'application/json')
            ->withAddedHeader('charset', 'utf-8');
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
