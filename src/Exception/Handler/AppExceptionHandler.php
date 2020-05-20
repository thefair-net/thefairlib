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

namespace TheFairLib\Exception\Handler;

use TheFairLib\Constants\InfoCode;
use TheFairLib\Constants\ServerCode;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * 统一异常处理
 *
 * Class AppExceptionHandler
 * @package TheFairLib\Exception\Handler
 */
class AppExceptionHandler extends ExceptionHandler
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * 默认异常处理
     *
     * @param Throwable $throwable
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->logger->error(sprintf('%s in %s code %s[%s]', $throwable->getMessage(), $throwable->getLine(), $throwable->getFile(), $throwable->getCode()));
        $this->logger->error($throwable->getTraceAsString());
        $result = $this->serviceResponse->showError(
            $throwable->getMessage(),
            ['data' => $response->getBody(), 'exception' => get_class($throwable)],
            $throwable->getCode() > 0 ? $throwable->getCode() : InfoCode::CODE_ERROR
        );
        return $response->withStatus($throwable->getHttpStatus ?? ServerCode::BAD_REQUEST)
            ->withAddedHeader('content-type', 'application/json')
            ->withAddedHeader('charset', 'utf-8')
            ->withBody(new SwooleStream(encode($result)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
