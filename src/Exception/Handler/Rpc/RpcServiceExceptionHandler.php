<?php
declare(strict_types=1);

namespace TheFairLib\Exception\Handler\Rpc;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Context\Context;
use Psr\Http\Message\ServerRequestInterface;
use TheFairLib\Constants\InfoCode;
use TheFairLib\Contract\ResponseBuilderInterface;
use TheFairLib\Exception\BusinessException;
use TheFairLib\Exception\EmptyException;
use TheFairLib\Exception\Handler\ExceptionHandler;
use TheFairLib\Exception\ServiceException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class RpcServiceExceptionHandler extends ExceptionHandler
{

    /**
     * @Inject
     * @var ResponseBuilderInterface
     */
    protected $responseBuilder;

    /**
     * @Inject
     * @var \TheFairLib\Contract\ResponseInterface
     */
    protected $serviceResponse;


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
         * @var ServiceException $throwable
         */
        $data = $throwable->getData();

        $result = $this->serviceResponse->showError(
            $throwable->getMessage(),
            $data,
            (int)$throwable->getCode()
        );
        return $this->responseBuilder->buildResponse(
            Context::get(ServerRequestInterface::class),
            $result
        );
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ServiceException ||
            $throwable instanceof BusinessException ||
            $throwable instanceof EmptyException;
    }
}
