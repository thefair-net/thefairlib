<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace TheFairLib\Service\Swoole\JsonRpc;


use TheFairLib\Utility\Utility;

class JsonLengthPacker
{
    /**
     * @var string
     */
    protected $type;

    /**
     * @var int
     */
    protected $length;

    protected $defaultOptions = [
        'package_length_type' => 'N',
        'package_body_offset' => 4,
    ];

    public static $instance;

    /**
     * @param array $options
     * @return JsonLengthPacker
     */
    public static function instance(array $options = [])
    {
        $class = get_called_class();
        if (empty(self::$instance)) {
            self::$instance = new $class($options);
        }
        return self::$instance;
    }

    public function __construct(array $options = [])
    {
        $options = array_merge($this->defaultOptions, $options['settings'] ?? []);

        $this->type = $options['package_length_type'];
        $this->length = $options['package_body_offset'];
    }

    public function pack($data): string
    {
        $data = Utility::encode($data);
        return pack($this->type, strlen($data)) . $data;
    }

    public function unpack(string $data)
    {
        $data = substr($data, $this->length);
        return Utility::decode($data);
    }
}
