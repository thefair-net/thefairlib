<?php
declare(strict_types=1);

namespace TheFairLib\Exception\Handler;

use Hyperf\Di\Annotation\Inject;
use TheFairLib\Exception\BusinessException;
use TheFairLib\Exception\EmptyException;
use TheFairLib\Exception\ServiceException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * 业务异常处理
 *
 * Class ServiceExceptionHandler
 * @package TheFairLib\Exception\Handler
 */
class ServiceExceptionHandler extends ExceptionHandler
{

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
            array_merge_recursive($data, [
                'file' => str_replace(BASE_PATH, '.', $throwable->getFile()),
                'line' => $throwable->getLine(),
            ]),
            (int)$throwable->getCode()
        );
        return $response->withBody(new SwooleStream(encode($result)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ServiceException ||
            $throwable instanceof BusinessException ||
            $throwable instanceof EmptyException;
    }
}
