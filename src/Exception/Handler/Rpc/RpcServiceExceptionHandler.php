<?php
declare(strict_types=1);

namespace TheFairLib\Exception\Handler\Rpc;

use TheFairLib\Constants\ServerCode;
use TheFairLib\Exception\BusinessException;
use TheFairLib\Exception\EmptyException;
use TheFairLib\Exception\Handler\ExceptionHandler;
use TheFairLib\Exception\ServiceException;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class RpcServiceExceptionHandler extends ExceptionHandler
{

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

        $result = $this->serviceResponse->showError($throwable->getMessage(), array_merge($data, ['exception' => get_class($throwable)]), $throwable->getCode());
        return $response->withStatus(ServerCode::OK);
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ServiceException ||
            $throwable instanceof BusinessException ||
            $throwable instanceof EmptyException;
    }
}
