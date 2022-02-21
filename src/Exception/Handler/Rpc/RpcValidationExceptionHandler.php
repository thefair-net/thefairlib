<?php


namespace TheFairLib\Exception\Handler\Rpc;

use Hyperf\Validation\ValidationExceptionHandler;
use TheFairLib\Constants\InfoCode;
use TheFairLib\Contract\ResponseBuilderInterface;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Context\Context;
use Hyperf\Validation\ValidationException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class RpcValidationExceptionHandler extends ValidationExceptionHandler
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
        $result = $this->serviceResponse->showError(
            $throwable->validator->errors()->first(),
            [
                'exception' => get_class($throwable),
                'errors' => $throwable->validator->errors(),
            ],
            (int)$throwable->getCode() > 0 ? (int)$throwable->getCode() : InfoCode::CODE_ERROR
        );
        return $this->responseBuilder->buildResponse(
            Context::get(ServerRequestInterface::class),
            $result
        );
    }

    public function isValid(Throwable $throwable): bool
    {
        return $throwable instanceof ValidationException;
    }
}
