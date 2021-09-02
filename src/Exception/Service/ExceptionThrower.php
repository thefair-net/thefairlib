<?php

namespace TheFairLib\Exception\Service;

use Throwable;

final class ExceptionThrower
{
    /**
     * @var Throwable
     */
    private $throwable;

    public function __construct(Throwable $throwable)
    {
        $this->throwable = $throwable;
    }

    public function getThrowable(): Throwable
    {
        return $this->throwable;
    }
}
