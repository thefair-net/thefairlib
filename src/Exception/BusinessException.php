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
use Throwable;

/**
 * 业务异常，只能抛 code
 *
 * Class BusinessException
 * @package TheFairLib\Exception
 */
class BusinessException extends ServiceException
{
    public function __construct(int $code, array $replace = [], array $data = [], Throwable $previous = null, int $httpStatus = ServerCode::BAD_REQUEST)
    {
        parent::__construct((string)$this->businessCode($code, $replace), $data, $code, $previous, $httpStatus);
    }

    /**
     * 业务 code 处理
     *
     * @param $code
     * @param $replace
     * @return mixed
     */
    protected function businessCode($code, $replace)
    {
        $message = InfoCode::getMessage($code, $replace);
        if (empty($message)) {
            foreach (['\App\Constants\InfoCode', '\App\Constants\ErrorCode', '\App\Constants\ServerCode'] as $className) {
                if (class_exists($className)) {
                    $message = $className::getMessage($code, $replace);
                    if (!empty($message)) {
                        return $message;
                    }
                }
            }
        }
        return $message;
    }
}
