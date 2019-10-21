<?php
namespace TheFairLib\Service\Swoole\Coroutine;

class SysCall
{

    public static function end($words)
    {
        return new RetVal($words);
    }
}