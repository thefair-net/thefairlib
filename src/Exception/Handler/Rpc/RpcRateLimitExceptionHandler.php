<?php


namespace TheFairLib\Exception\Handler\Rpc;

use Hyperf\Di\Annotation\Inject;
use Hyperf\RateLimit\Exception\RateLimitException;
use Hyperf\Utils\Context;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TheFairLib\Contract\ResponseBuilderInterface;
use TheFairLib\Exception\Handler\ExceptionHandler;
use Throwable;

/**
 * 服务限流处理
 *
 * Class RateLimitExceptionHandler
 * @package TheFairLib\Exception\Handler
 */
class RpcRateLimitExceptionHandler extends ExceptionHandler
{

    /**
     * @Inject
     * @var ResponseBuilderInterface
     */
    protected $responseBuilder;

    /**
     * @Inject()
     * @var \TheFairLib\Contract\ResponseInterface
     */
    protected $serviceResponse;

    /**
     * @param Throwable $throwable
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $this->stopPropagation();
        /**
         * @var RateLimitException $throwable
         */
        $body = $throwable->getMessage();
        $result = $this->serviceResponse->showError(
            __('message.rate_limit_error', ['host' => getServerLocalIp()]),
            [
                'error' => $body,
            ]
        );
        return $this->responseBuilder->buildResponse(
            Context::get(ServerRequestInterface::class),
            $result
        );
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof RateLimitException;
    }
}
