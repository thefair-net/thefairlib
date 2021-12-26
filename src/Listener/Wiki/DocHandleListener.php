<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Listener\Wiki;

use Hyperf\Di\Annotation\Inject;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\Filesystem\FilesystemFactory;
use Hyperf\Framework\Event\OnReceive;
use Hyperf\HttpServer\Router\Dispatched;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Utils\Context;
use League\Flysystem\FilesystemException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TheFairLib\Command\Wiki\DocumentGenerate;
use TheFairLib\Event\OnResponse;
use TheFairLib\Library\Logger\Logger;
use Throwable;

class DocHandleListener implements ListenerInterface
{

    /**
     * @Inject()
     * @var FilesystemFactory
     */
    public $factory;

    /**
     * {@inheritdoc}
     */
    public function listen(): array
    {
        return [
            OnResponse::class,
            OnReceive::class,
        ];
    }

    /**
     * @param object $event
     */
    public function process(object $event)
    {
        try {
            if ($event instanceof OnResponse) {
                if (config('docs.enable', false) && time() % (int)config('docs.response_result_gather_sharding', 1000) === 0) {
                    $this->writeResponseResult($event->request, $event->response);
                }
            }
            if ($event instanceof OnReceive) {
                if (config('docs.enable', false) && time() % (int)config('docs.response_result_gather_sharding', 1000) === 0) {
                    $this->writeResponseResult(Context::get(ServerRequestInterface::class), Context::get('server:response_body'));
                }
            }
        } catch (Throwable $e) {
            Logger::get()->error('doc_response', [
                'error' => container(FormatterInterface::class)->format($e),
            ]);
        }
    }

    /**
     * 采集返回结果
     *
     * @param ServerRequestInterface $request
     * @param $response
     * @throws Throwable
     * @throws FilesystemException
     */
    protected function writeResponseResult(ServerRequestInterface $request, $response)
    {
        $data = [];
        if ($response instanceof ResponseInterface) {
            $data = decode((string)$response->getBody());
        }
        if (!empty($response) && is_array($response)) {
            $data = $response;
        }
        $dispatched = $request->getAttribute(Dispatched::class);
        if ($dispatched instanceof Dispatched && $dispatched->handler instanceof Handler) {
            $path = container(DocumentGenerate::class)->getResponseResultPath($dispatched->handler);
            $refreshResponseList = config('docs.refresh_response_file');
            $isRefreshPath = false;
            foreach ($refreshResponseList as $item) {
                if ($item == $path || strpos($path, $item)) {
                    $isRefreshPath = true;
                    break;
                }
            }
            if (0 === arrayGet($data, 'code') && (!$this->factory->get('local')->fileExists($path) || $isRefreshPath)) {
                $this->factory->get('local')->write($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
    }
}
