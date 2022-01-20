<?php

declare(strict_types=1);

namespace TheFairLib;

use Hyperf\AsyncQueue\Driver\RedisDriver;
use Hyperf\Cache\AnnotationManager;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\LengthAwarePaginatorInterface;
use Hyperf\Contract\NormalizerInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Flysystem\OSS\Adapter;
use Hyperf\HttpServer\CoreMiddleware;
use Hyperf\HttpServer\ResponseEmitter;
use Hyperf\JsonRpc\JsonRpcPoolTransporter;
use Hyperf\JsonRpc\JsonRpcTransporter;
use Hyperf\JsonRpc\Pool\RpcConnection;
use Hyperf\ServiceGovernanceNacos\Listener\MainWorkerStartListener;
use Hyperf\ServiceGovernanceNacos\Listener\OnShutdownListener;
use Hyperf\TfConfig\ConfigFactory;
use Hyperf\Utils\Serializer\SimpleNormalizer;
use Overtrue\Flysystem\Qiniu\QiniuAdapter;
use TheFairLib\Contract\LockInterface;
use TheFairLib\Contract\RequestParamInterface;
use TheFairLib\Contract\ResponseBuilderInterface;
use TheFairLib\Contract\ResponseInterface;
use TheFairLib\Contract\StsInterface;
use TheFairLib\Contract\WeChatFactoryInterface;
use TheFairLib\Library\File\AliYun\Sts;
use TheFairLib\Library\Http\Request\RequestParam;
use TheFairLib\Library\Http\ResponseBuilderFactory;
use TheFairLib\Library\Http\ServiceResponse;
use TheFairLib\Library\Lock\RedisLockFactory;
use TheFairLib\Library\Logger\StdoutLoggerFactory;
use TheFairLib\Library\WeChat\EasyWeChat\WeChatFactoryService;
use TheFairLib\Listener\DbQueryExecutedListener;
use TheFairLib\Listener\ErrorHandleListener;
use TheFairLib\Listener\Logger\LoggerHandleListener;
use TheFairLib\Listener\RouterHandleListener;
use TheFairLib\Listener\Server\TermSignalHandler;
use TheFairLib\Listener\Server\WorkerErrorHandleListener;
use TheFairLib\Listener\Server\WorkerExitHandleListener;
use TheFairLib\Listener\Server\WorkerStopHandleListener;
use TheFairLib\Listener\ValidatorHandleListener;
use TheFairLib\Listener\Wiki\DocHandleListener;
use TheFairLib\Middleware\Core\ServiceMiddleware;
use TheFairLib\Model\Paginator\LengthAwarePaginator;
use TheFairLib\Process\Nacos\InstanceBeatProcess;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [
                ConfigInterface::class => ConfigFactory::class,
                CoreMiddleware::class => ServiceMiddleware::class,
                ResponseInterface::class => ServiceResponse::class,
                StdoutLoggerInterface::class => StdoutLoggerFactory::class,
                LockInterface::class => RedisLockFactory::class,
                NormalizerInterface::class => SimpleNormalizer::class,
                ResponseBuilderInterface::class => ResponseBuilderFactory::class,
                LengthAwarePaginatorInterface::class => LengthAwarePaginator::class,
                RequestParamInterface::class => RequestParam::class,
                WeChatFactoryInterface::class => WeChatFactoryService::class,
                JsonRpcTransporter::class => JsonRpcPoolTransporter::class,
                StsInterface::class => Sts::class,
            ],
            'listeners' => [
                DocHandleListener::class,
                ErrorHandleListener::class,
                RouterHandleListener::class,
                ValidatorHandleListener::class,
                DbQueryExecutedListener::class,
                LoggerHandleListener::class,
                TermSignalHandler::class,
//                MainWorkerStartListener::class,
//                OnShutdownListener::class,
//                WorkerStopHandleListener::class,
//                WorkerErrorHandleListener::class,
//                WorkerExitHandleListener::class,
            ],
            'processes' => [
                InstanceBeatProcess::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                    'class_map' => $this->getClassMap(),
                ],
            ],
            'publish' => [
                [
                    'id' => 'ServerCode',
                    'description' => 'The message bag for validation.',
                    'source' => __DIR__ . '/../publish/Constants/ServerCode.php',
                    'destination' => BASE_PATH . '/app/Constants/ServerCode.php',
                ],
                [
                    'id' => 'InfoCode',
                    'description' => 'The message bag for validation.',
                    'source' => __DIR__ . '/../publish/Constants/InfoCode.php',
                    'destination' => BASE_PATH . '/app/Constants/InfoCode.php',
                ],
                [
                    'id' => 'translation',
                    'description' => 'The config for translation',
                    'source' => __DIR__ . '/../publish/translation.php',
                    'destination' => BASE_PATH . '/config/autoload/translation.php',
                ],
                [
                    'id' => 'validation',
                    'description' => 'The config for validation',
                    'source' => __DIR__ . '/../publish/validation.php',
                    'destination' => BASE_PATH . '/config/autoload/validation.php',
                ],
                [
                    'id' => 'zh_CN',
                    'description' => 'The message bag for validation.',
                    'source' => __DIR__ . '/../publish/languages/zh_CN/validation.php',
                    'destination' => BASE_PATH . '/config/i18n/zh_CN/validation.php',
                ],
                [
                    'id' => 'en',
                    'description' => 'The message bag for validation.',
                    'source' => __DIR__ . '/../publish/languages/en/validation.php',
                    'destination' => BASE_PATH . '/config/i18n/en/validation.php',
                ],
                [
                    'id' => 'auth',
                    'description' => 'The config for auth.',
                    'source' => __DIR__ . '/../publish/auth.php',
                    'destination' => BASE_PATH . '/config/autoload/auth.php',
                ],
                [
                    'id' => 'email',
                    'description' => 'The config for email',
                    'source' => __DIR__ . '/../publish/email.php',
                    'destination' => BASE_PATH . '/config/autoload/email.php',
                ],
                [
                    'id' => 'file',
                    'description' => 'The config for file',
                    'source' => __DIR__ . '/../publish/file.php',
                    'destination' => BASE_PATH . '/config/autoload/file.php',
                ],
                [
                    'id' => 'signal',
                    'description' => 'The config for signal',
                    'source' => __DIR__ . '/../publish/signal.php',
                    'destination' => BASE_PATH . '/config/autoload/signal.php',
                ],
                [
                    'id' => 'lock',
                    'description' => 'The config for lock',
                    'source' => __DIR__ . '/../publish/lock.php',
                    'destination' => BASE_PATH . '/config/autoload/lock.php',
                ],
                [
                    'id' => 'env',
                    'description' => 'The message bag for env.',
                    'source' => __DIR__ . '/../publish/.env.example',
                    'destination' => BASE_PATH . '/.env.example',
                ],
                [
                    'id' => 'dev_start',
                    'description' => 'The message bag for watch.',
                    'source' => __DIR__ . '/../publish/bin/dev_start.php',
                    'destination' => BASE_PATH . '/dev_start.php',
                ],
                [
                    'id' => 'doc_test',
                    'description' => 'The message bag for test.',
                    'source' => __DIR__ . '/../publish/test/Cases/DocTest.php',
                    'destination' => BASE_PATH . '/test/Cases/DocTest.php',
                ],
                [
                    'id' => 'index_test',
                    'description' => 'The message bag for test.',
                    'source' => __DIR__ . '/../publish/test/Cases/ExampleTest.php',
                    'destination' => BASE_PATH . '/test/Cases/ExampleTest.php',
                ],
                [
                    'id' => 'service',
                    'description' => 'The message test.service.',
                    'source' => __DIR__ . '/../publish/bin/test.service',
                    'destination' => BASE_PATH . sprintf('/bin/%s.service', str_replace('_service', '', env('APP_NAME', 'test'))),
                ],
            ],
        ];
    }

    /**
     * class map 重写
     *
     * @return array
     */
    protected function getClassMap(): array
    {
        $baseVendor = BASE_PATH . '/vendor/';
        $classMapPath = $baseVendor . 'thefair/thefairlib/class_map/';
        $data = [
            $baseVendor . 'hyperf/http-server/src/ResponseEmitter.php' => [
                ResponseEmitter::class => $classMapPath . 'Hyperf/HttpServer/ResponseEmitter.php',
            ],
            $baseVendor . 'hyperf/json-rpc/src/JsonRpcPoolTransporter.php' => [
                JsonRpcPoolTransporter::class => $classMapPath . 'Hyperf/JsonRpc/JsonRpcPoolTransporter.php',
            ],
            $baseVendor . 'hyperf/json-rpc/src/Pool/RpcConnection.php' => [
                RpcConnection::class => $classMapPath . 'Hyperf/JsonRpc/Pool/RpcConnection.php',
            ],
            $baseVendor . 'hyperf/async-queue/src/Driver/RedisDriver.php' => [
                RedisDriver::class => $classMapPath . 'Hyperf/AsyncQueue/Driver/RedisDriver.php',
            ],
            $baseVendor . 'hyperf/flysystem-oss/src/Adapter.php' => [
                Adapter::class => $classMapPath . 'Hyperf/Flysystem/OSS/Adapter.php',
            ],
            $baseVendor . 'overtrue/flysystem-qiniu/src/QiniuAdapter.php' => [
                QiniuAdapter::class => $classMapPath . 'Overtrue/Flysystem/Qiniu/QiniuAdapter.php',
            ],
            $baseVendor . 'hyperf/cache/src/AnnotationManager.php' => [
                AnnotationManager::class => $classMapPath . 'Hyperf/Cache/AnnotationManager.php',
            ],
        ];
        $classMap = [];
        foreach ($data as $file => $class) {
            if (file_exists($file)) {
                $classMap = array_merge($classMap, $class);
            }
        }
        return $classMap;
    }
}
