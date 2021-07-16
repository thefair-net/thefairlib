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

use Hyperf\Di\Annotation\Inject;
use Hyperf\Validation\ValidationException;
use TheFairLib\Constants\InfoCode;
use TheFairLib\Constants\ServerCode;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use TheFairLib\Exception\BusinessException;
use TheFairLib\Exception\EmptyException;
use TheFairLib\Exception\Service\RetryException;
use TheFairLib\Exception\Service\TermException;
use TheFairLib\Exception\ServiceException;
use TheFairLib\Library\Logger\Logger;
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
     * @Inject
     * @var \TheFairLib\Contract\ResponseInterface
     */
    protected $serviceResponse;

    /**
     * 默认异常处理
     *
     * @param Throwable $throwable
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $data = [];
        $status = ServerCode::BAD_REQUEST;
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
                $status = $throwable->getHttpStatus();
                break;
            case RetryException::class:
            case TermException::class:
                $msg = $throwable->getMessage();
                $data = $throwable->getData();
                $status = $throwable->getHttpStatus();
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
                getHttpLogArguments()
            )
        );
        $result = $this->serviceResponse->showError(
            $throwable->getMessage(),
            [
                'data' => $response->getBody(),
                'file' => str_replace(BASE_PATH, '.', $throwable->getFile()),
                'line' => $throwable->getLine(),
            ],
            $code > 0 ? $code : InfoCode::CODE_ERROR
        );
        return $response->withStatus($status)
            ->withAddedHeader('content-type', 'application/json')
            ->withAddedHeader('charset', 'utf-8')
            ->withBody(new SwooleStream(encode($result)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}
