<?php


namespace TheFairLib\Exception\Handler\Rpc;

use Hyperf\JsonRpc\Packer\JsonLengthPacker;
use TheFairLib\Exception\ServiceException;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\JsonRpc\DataFormatter;
use Hyperf\JsonRpc\Packer\JsonEofPacker;
use Hyperf\JsonRpc\PathGenerator;
use Hyperf\JsonRpc\ResponseBuilder;
use Hyperf\Rpc\ProtocolManager;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class RpcValidationExceptionHandler extends \Hyperf\Validation\ValidationExceptionHandler
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
        //重写 Throwable ，不然 $throwable->getMessage 没有办法自定义
        $serviceThrowable = new ServiceException($throwable->validator->errors()->first());
        $container = ApplicationContext::getContainer();
        $responseBuilder = make(ResponseBuilder::class, [
            'dataFormatter' => $container->get(DataFormatter::class),
            'packer' => $container->get(JsonLengthPacker::class),
        ]);
        /**
         * @var ResponseBuilder $responseBuilder
         */
        $response = $responseBuilder->buildErrorResponse(
            Context::get(ServerRequestInterface::class),
            ResponseBuilder::SERVER_ERROR,
            $serviceThrowable
        );

        return $response;
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}
