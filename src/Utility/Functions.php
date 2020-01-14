<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file Functions.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-01-13 17:22:00
 *
 **/


if (! function_exists('env')) {
    /**
     * Gets the value of an environment variable.
     *
     * @param string $key
     * @param null|mixed $default
     * @return array|bool|false|mixed|string|void
     */
    function env($key, $default = null)
    {
        $value = getenv($key);
        if ($value === false) {
            return $value instanceof \Closure ? $value() : $value;
        }
        switch (strtolower($value)) {
            case 'true':
            case '(true)':
                return true;
            case 'false':
            case '(false)':
                return false;
            case 'empty':
            case '(empty)':
                return '';
            case 'null':
            case '(null)':
                return;
        }
        if (($valueLength = strlen($value)) > 1 && $value[0] === '"' && $value[$valueLength - 1] === '"') {
            return substr($value, 1, -1);
        }
        return $value;
    }
}