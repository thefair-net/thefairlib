<?php


namespace TheFairLib\Exception\Handler;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\RateLimit\Exception\RateLimitException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * 服务限流处理
 *
 * Class RateLimitExceptionHandler
 * @package TheFairLib\Exception\Handler
 */
class RateLimitExceptionHandler extends \Hyperf\Validation\ValidationExceptionHandler
{

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
        $result = $this->serviceResponse->showError(__('message.rate_limit_error', ['host' => getServerLocalIp()]), ['error' => $body]);
        return $response->withBody(new SwooleStream(encode($result)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof RateLimitException;
    }
}
