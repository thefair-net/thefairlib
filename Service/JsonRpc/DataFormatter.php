<?php


namespace TheFairLib\Service\JsonRpc;


use Exception;
use TheFairLib\Utility\Utility;

class DataFormatter
{

    public static $instance;

    /**
     * @return DataFormatter
     */
    public static function instance()
    {
        $class = get_called_class();
        if (empty(self::$instance)) {
            self::$instance = new $class();
        }
        return self::$instance;
    }

    public function formatRequest($data)
    {
        return [
            'jsonrpc' => '2.0',
            'method' => $data[0],
            'params' => $data[1],
            'id' => $data[2],
        ];
    }

    public function formatResponse($data)
    {
        return [
            'jsonrpc' => '2.0',
            'id' => $data[0],
            'result' => $data[1],
        ];
    }

    public function formatErrorResponse($data)
    {
        $id = Utility::arrayGet($data, 0);
        $code = Utility::arrayGet($data, 1);
        $message = Utility::arrayGet($data, 2);
        $data = Utility::arrayGet($data, 3);
        if (isset($data) && $data instanceof Exception) {
            $data = [
                'class' => get_class($data),
                'code' => $data->getCode(),
                'message' => $data->getMessage(),
            ];
        }
        return [
            'jsonrpc' => '2.0',
            'id' => $id,
            'error' => [
                'code' => $code,
                'message' => $message,
                'data' => $data,
            ],
        ];
    }

    public function generate()
    {
        $us = strstr(microtime(), ' ', true);
        return strval($us * 1000 * 1000) . rand(100, 999);
    }
}
