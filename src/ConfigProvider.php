<?php

declare(strict_types=1);

namespace TheFairLib;

use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\NormalizerInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Devtool\VendorPublishCommand;
use Hyperf\HttpServer\CoreMiddleware;
use Hyperf\TfConfig\ConfigFactory;
use Hyperf\Utils\Serializer\SimpleNormalizer;
use TheFairLib\Contract\LockInterface;
use TheFairLib\Contract\ResponseBuilderInterface;
use TheFairLib\Contract\ResponseInterface;
use TheFairLib\Library\Http\ResponseBuilderFactory;
use TheFairLib\Library\Http\ServiceResponse;
use TheFairLib\Library\Lock\RedisLockFactory;
use TheFairLib\Library\Logger\StdoutLoggerFactory;
use TheFairLib\Listener\DbQueryExecutedListener;
use TheFairLib\Listener\ErrorHandleListener;
use TheFairLib\Listener\Logger\LoggerHandleListener;
use TheFairLib\Listener\RouterHandleListener;
use TheFairLib\Listener\Server\WorkerErrorHandleListener;
use TheFairLib\Listener\Server\WorkerExitHandleListener;
use TheFairLib\Listener\Server\WorkerStopHandleListener;
use TheFairLib\Listener\ValidatorHandleListener;
use TheFairLib\Middleware\Core\ServiceMiddleware;

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
            ],
            'listeners' => [
                ErrorHandleListener::class,
                RouterHandleListener::class,
                ValidatorHandleListener::class,
                DbQueryExecutedListener::class,
                LoggerHandleListener::class,
                WorkerStopHandleListener::class,
                WorkerErrorHandleListener::class,
                WorkerExitHandleListener::class,
            ],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
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
                    'id' => 'push',
                    'description' => 'The config for push',
                    'source' => __DIR__ . '/../publish/push.php',
                    'destination' => BASE_PATH . '/config/autoload/push.php',
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
                [
                    'id' => 'init-proxy',
                    'description' => 'The message init-proxy.',
                    'source' => __DIR__ . '/../publish/bin/init-proxy.sh',
                    'destination' => BASE_PATH . sprintf('/bin/init-proxy.sh'),
                ],
            ],
        ];
    }
}
