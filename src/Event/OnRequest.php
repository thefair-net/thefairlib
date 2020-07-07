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
namespace TheFairLib\Event;

use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;

class OnRequest
{

    /**
     * @var SwooleRequest
     */
    public $request;

    /**
     * @var SwooleResponse
     */
    public $response;


    public function __construct(SwooleRequest $request, SwooleResponse $response)
    {
        $this->request = $request;
        $this->response = $response;
    }
}
