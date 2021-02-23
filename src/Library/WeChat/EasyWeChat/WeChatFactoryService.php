<?php

namespace TheFairLib\Library\WeChat\EasyWeChat;

use EasyWeChat\Factory;
use EasyWeChat\OfficialAccount\Application;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Guzzle\CoroutineHandler;
use Hyperf\HttpMessage\Upload\UploadedFile;
use Hyperf\HttpServer\Contract\RequestInterface;
use TheFairLib\Constants\WeChatBase;
use TheFairLib\Contract\WeChatFactoryInterface;
use TheFairLib\Exception\ServiceException;
use TheFairLib\Library\Cache\Redis;
use TheFairLib\Library\WeChat\EasyWeChat\Core\WeChatConfig;
use Throwable;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Request;

class WeChatFactoryService implements WeChatFactoryInterface
{

    /**
     * @Inject()
     * @var WeChatConfig
     */
    public $weChatConfig;

    /**
     * @Inject
     * @var RequestInterface
     */
    protected $request;

    /**
     * 实例
     *
     * @param string $type
     * @param string $appLabel
     * @param string $category
     * @return Application|\EasyWeChat\MiniProgram\Application|\EasyWeChat\OpenPlatform\Application
     */
    public function getApp(string $type, string $appLabel, string $category = '')
    {
        try {
            $config = $this->weChatConfig->getConfigInfo($appLabel, $category);
            switch ($type) {
                case WeChatBase::OFFICIAL:
                case WeChatBase::MINI_PROGRAM:
                case WeChatBase::OPEN_PLATFORM:
                    /**
                     * @var Application|\EasyWeChat\MiniProgram\Application|\EasyWeChat\OpenPlatform\Application $app
                     */
                    $app = Factory::$type($config->getConfig()->toArray());
                    return $this->setCommon($app, $config);
                default:
                    throw new ServiceException('project_id error');
            }
        } catch (ServiceException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ServiceException('get app factory error: ' . $e->getMessage());
        }
    }

    /**
     * @param Application|\EasyWeChat\MiniProgram\Application|\EasyWeChat\OpenPlatform\Application $app
     * @param WeChatConfig $weChatConfig
     * @return \EasyWeChat\MiniProgram\Application|Application|\EasyWeChat\OpenPlatform\Application
     */
    protected function setCommon($app, WeChatConfig $weChatConfig)
    {
        $handler = new CoroutineHandler();
        // 设置 HttpClient，部分接口直接使用了 http_client。
        $config = $app['config']->get('http', []);
        $config['handler'] = $stack = HandlerStack::create($handler);
        $app->rebind('http_client', new Client($config));

        // 部分接口在请求数据时，会根据 guzzle_handler 重置 Handler
        $app['guzzle_handler'] = $handler;
        $app->rebind('cache', Redis::getContainer($weChatConfig->getConfig()->get('cache.pool_name', 'default')));
        $app->rebind('request', $this->setRequest());

        if ($app instanceof Application) {
            // 如果使用的是 OfficialAccount，则还需要设置以下参数
            $app->oauth->setGuzzleOptions([
                'http_errors' => false,
                'handler' => $stack,
            ]);
        }
        return $app;
    }

    /**
     * 重写 Request
     *
     * @return Request
     */
    protected function setRequest(): Request
    {
        $get = $this->request->getQueryParams();
        $post = $this->request->getParsedBody();
        $cookie = $this->request->getCookieParams();
        $uploadFiles = $this->request->getUploadedFiles() ?? [];
        $server = $this->request->getServerParams();
        $xml = $this->request->getBody()->getContents();
        $files = [];
        /** @var UploadedFile $v */
        foreach ($uploadFiles as $k => $v) {
            $files[$k] = $v->toArray();
        }
        $request = new Request($get, $post, [], $cookie, $files, $server, $xml);
        $request->headers = new HeaderBag($this->request->getHeaders());
        return $request;
    }
}
