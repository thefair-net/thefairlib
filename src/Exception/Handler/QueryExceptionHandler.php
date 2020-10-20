<?php
declare(strict_types=1);

namespace TheFairLib\Exception\Handler;

use Hyperf\Database\Exception\QueryException;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 * sql 数据库异常处理
 *
 * Class QueryExceptionHandler
 * @package TheFairLib\Exception\Handler
 */
class QueryExceptionHandler extends ExceptionHandler
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
         * @var QueryException $throwable
         */
        $result = $this->serviceResponse->showError($throwable->getMessage(), [
            'sql' => $throwable->getSql(),
            'bindings' => $throwable->getBindings(),
            'exception' => get_class($throwable)
        ], $throwable->getCode());
        return $response->withBody(new SwooleStream(encode($result)));
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof QueryException;
    }
}
