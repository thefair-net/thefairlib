<?php


namespace TheFairLib\Service\JsonRpc;


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
        $options = array_merge($this->defaultOptions, !empty($options['settings']) ? $options['settings'] : []);

        $this->type = $options['package_length_type'];
        $this->length = $options['package_body_offset'];
    }

    public function pack($data)
    {
        $data = Utility::encode($data);
        return pack($this->type, strlen($data)) . $data;
    }

    public function unpack($data)
    {
        $data = substr($data, $this->length);
        return Utility::decode($data);
    }
}
