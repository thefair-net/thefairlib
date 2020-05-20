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

namespace TheFairLib\Exception;

use TheFairLib\Constants\InfoCode;
use TheFairLib\Constants\ServerCode;
use Hyperf\Server\Exception\ServerException;
use Hyperf\Utils\Context;
use Throwable;

/**
 * @property array data
 * @property int httpStatus
 *
 * Class ServiceException
 * @package TheFairLib\Exception
 */
class ServiceException extends ServerException
{
    public function __construct(string $message, array $data = [], int $code = InfoCode::CODE_ERROR, Throwable $previous = null, int $httpStatus = ServerCode::BAD_REQUEST)
    {
        $this->data = $data;
        $this->httpStatus = $httpStatus;
        parent::__construct($message, $code, $previous);
    }

    public function __set($name, $value)
    {
        if (!in_array($name, ['data', 'httpStatus'])) {
            return;
        }
        Context::set(__CLASS__ . ':' . $name, $value);
    }

    public function __get($name)
    {
        if (!in_array($name, ['data', 'httpStatus'])) {
            return null;
        }
        return Context::get(__CLASS__ . ':' . $name);
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return int
     */
    public function getHttpStatus()
    {
        return $this->httpStatus;
    }
}
