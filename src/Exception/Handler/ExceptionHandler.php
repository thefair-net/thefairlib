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

namespace TheFairLib\Exception\Handler;

use TheFairLib\Contract\ResponseInterface;
use Hyperf\Di\Annotation\Inject;

abstract class ExceptionHandler extends \Hyperf\ExceptionHandler\ExceptionHandler
{
    /**
     * @Inject
     * @var ResponseInterface
     */
    protected $serviceResponse;
}
