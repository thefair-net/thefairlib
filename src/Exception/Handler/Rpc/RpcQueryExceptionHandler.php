<?php
declare(strict_types=1);

namespace TheFairLib\Exception\Handler\Rpc;

use Hyperf\Database\Exception\QueryException;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Utils\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TheFairLib\Contract\ResponseBuilderInterface;
use TheFairLib\Exception\Handler\ExceptionHandler;
use Throwable;

/**
 * sql 数据库异常处理
 *
 * Class RpcQueryExceptionHandler
 * @package TheFairLib\Exception\Handler\Rpc
 */
class RpcQueryExceptionHandler extends ExceptionHandler
{

    /**
     * @Inject
     * @var \TheFairLib\Contract\ResponseInterface
     */
    protected $serviceResponse;

    /**
     * @Inject
     * @var ResponseBuilderInterface
     */
    protected $responseBuilder;

    /**
     * Handle the exception, and return the specified result.
     * @param Throwable $throwable
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        // 阻止异常冒泡
        $this->stopPropagation();
        /**
         * @var QueryException $throwable
         */
        $result = $this->serviceResponse->showError($throwable->getMessage(), [
            'sql' => $throwable->getSql(),
            'bindings' => $throwable->getBindings(),
            'exception' => get_class($throwable)
        ], $throwable->getCode());
        return $this->responseBuilder->buildResponse(
            Context::get(ServerRequestInterface::class),
            $result
        );
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof QueryException;
    }
}
