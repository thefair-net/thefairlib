<?php


namespace TheFairLib\Listener;

use TheFairLib\Contract\ValidatorExtendInterface;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Validation\Event\ValidatorFactoryResolved;
use TheFairLib\Library\Validator\Get;
use TheFairLib\Library\Validator\Integer;
use TheFairLib\Library\Validator\Mobile;
use TheFairLib\Library\Validator\Post;
use TheFairLib\Library\Validator\Str;
use TheFairLib\Library\Validator\Strings;

class ValidatorHandleListener implements ListenerInterface
{

    /**
     * @var array
     */
    private $config = [
        'extend' => [
            Post::class,
            Get::class,
            Str::class,
            Strings::class,
            Mobile::class,
            Integer::class,
        ],
    ];

    public function listen(): array
    {
        return [
            ValidatorFactoryResolved::class,
        ];
    }

    public function process(object $event)
    {
        if ($event instanceof ValidatorFactoryResolved) {
            $validatorFactory = $event->validatorFactory;
            // 注册了 自定义验证器
            foreach ($this->config['extend'] as $className) {
                $class = make($className);
                $status = $class instanceof ValidatorExtendInterface &&
                    method_exists($class, 'extend') &&
                    method_exists($class, 'replacer');
                if ($status) {
                    // 规则验证
                    $validatorFactory->extend($class->getRuleName(), sprintf("%s@extend", $className));
                    // 当创建一个自定义验证规则时，你可能有时候需要为错误信息定义自定义占位符这里扩展了 :foo 占位符
                    $validatorFactory->replacer($class->getRuleName(), sprintf("%s@replacer", $className));
                }
            }
        }
    }
}
