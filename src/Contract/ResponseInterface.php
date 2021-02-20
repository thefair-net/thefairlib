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

namespace TheFairLib\Contract;

use TheFairLib\Constants\InfoCode;

interface ResponseInterface
{
    /**
     * 正常返回.
     *
     * @param $result
     * @param string $msg
     * @param int $code
     * @param string $action
     * @return mixed
     */
    public function showResult(array $result, string $msg = '', int $code = 0, string $action = 'toast');

    /**
     * 失败返回.
     *
     * @param $error
     * @param array $result
     * @param int $code
     * @param string $action
     * @return mixed
     */
    public function showError(string $error, array $result = [], int $code = InfoCode::CODE_ERROR, string $action = 'toast');

    /**
     * 成功
     *
     * @param string $msg
     * @param string $action
     * @return mixed
     */
    public function showSuccess(string $msg = '', string $action = 'toast');
}
