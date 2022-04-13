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
use Hyperf\Context\Context;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ServerRequestInterface;
use TheFairLib\Constants\InfoCode;
use TheFairLib\Constants\ServerCode;
use TheFairLib\Contract\ResponseBuilderInterface;
use TheFairLib\Exception\BusinessException;
use TheFairLib\Exception\EmptyException;
use TheFairLib\Exception\Handler\ExceptionHandler;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use TheFairLib\Exception\Service\RetryException;
use TheFairLib\Exception\Service\TermException;
use TheFairLib\Exception\ServiceException;
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

    /**
     * @Inject
     * @var \TheFairLib\Contract\ResponseInterface
     */
    protected $serviceResponse;

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
        $data = [];
        $fun = 'error';
        $code = (int)$throwable->getCode() ?? 0;
        switch (get_class($throwable)) {
            case ValidationException::class:
                /**
                 * @var ValidationException $throwable
                 */
                $msg = $throwable->validator->errors()->first();
                break;
            case BusinessException::class:
            case EmptyException::class:
            case ServiceException::class:
                $msg = $throwable->getMessage();
                $data = $throwable->getData();
                break;
            case RetryException::class:
            case TermException::class:
                $msg = $throwable->getMessage();
                $data = $throwable->getData();
                $fun = 'warning';
                break;
            default:
                $msg = $throwable->getMessage();
                break;
        }
        Logger::get()->$fun(
            sprintf('error_exception:%s', get_class($throwable)),
            array_merge_recursive(
                [
                    'msg' => $msg,
                    'line' => $throwable->getLine(),
                    'file' => $throwable->getFile(),
                    'code' => $code,
                    'trace_string' => $throwable->getTraceAsString(),
                    'ext_data' => $data,
                ],
                getRpcLogArguments()
            )
        );

        $result = $this->serviceResponse->showError(
            $throwable->getMessage(),
            ['data' => $response->getBody()],
            $code > 0 ? $code : InfoCode::CODE_ERROR
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
