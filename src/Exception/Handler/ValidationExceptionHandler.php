<?php


namespace TheFairLib\Exception\Handler;

use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * 参数或表单验证异常处理
 *
 * Class ValidationExceptionHandler
 * @package TheFairLib\Exception\Handler
 */
class ValidationExceptionHandler extends \Hyperf\Validation\ValidationExceptionHandler
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
         * @var ValidationException $throwable
         */
        $body = $throwable->validator->errors()->first();
        $result = $this->serviceResponse->showError($body, ['data' => $throwable->validator->errors()->all()]);
        return $response->withBody(new SwooleStream(encode($result)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}
