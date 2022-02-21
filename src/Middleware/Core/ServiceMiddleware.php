<?php

declare(strict_types=1);

namespace TheFairLib\Middleware\Core;

use Hyperf\HttpServer\CoreMiddleware;
use Psr\EventDispatcher\EventDispatcherInterface;
use TheFairLib\Constants\ServerCode;
use Hyperf\Di\Annotation\Inject;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\Context\Context;
use Hyperf\Utils\Contracts\Arrayable;
use Hyperf\Utils\Contracts\Jsonable;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TheFairLib\Event\OnResponse;

class ServiceMiddleware extends CoreMiddleware
{

    /**
     * @Inject
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @Inject()
     * @var \TheFairLib\Contract\ResponseInterface
     */
    protected $serviceResponse;

    public function dispatch(ServerRequestInterface $request): ServerRequestInterface
    {
        $routes = $this->dispatcher->dispatch($request->getMethod(), camelize($request->getUri()->getPath()));
        $dispatched = new Dispatched($routes);
        return Context::set(ServerRequestInterface::class, $request->withAttribute(Dispatched::class, $dispatched));
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = parent::process($request, $handler);
        Context::set('server:response_body_size', $response->getBody()->getSize());
        $response = $response->withHeader('Server', env('SERVER_NAME', 'IIS'));
        $this->eventDispatcher->dispatch(new OnResponse($request, $response));
        return $response;
    }

    /**
     * Handle the response when cannot found any routes.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function handleNotFound(ServerRequestInterface $request): ResponseInterface
    {
        // 重写路由找不到的处理逻辑
        $result = $this->serviceResponse->showError('Not Found');
        return $this->response()->withStatus(ServerCode::HTTP_NOT_FOUND)
            ->withAddedHeader('content-type', 'application/json; charset=utf-8')
            ->withBody(new SwooleStream(encode($result)));
    }

    /**
     * 结果输出
     *
     * @param array|Arrayable|Jsonable|string $response
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     */
    protected function transferToResponse($response, ServerRequestInterface $request): ResponseInterface
    {
        return $this->response()
            ->withAddedHeader('content-type', 'application/json; charset=utf-8')
            ->withBody(new SwooleStream(encode($response)));
    }

}
