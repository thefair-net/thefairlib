<?php

namespace TheFairLib\Utility;

use InvalidArgumentException;

/**
 * Class AES.
 *
 * @author overtrue <i@overtrue.me>
 */
class AES
{
    /**
     * @param string $text
     * @param string $key
     * @param string $iv
     * @param int $option
     *
     * @return string
     */
    public static function encrypt(string $text, string $key, string $iv, int $option = OPENSSL_RAW_DATA): string
    {
        self::validateKey($key);
        self::validateIv($iv);

        return openssl_encrypt($text, self::getMode(), $key, $option, $iv);
    }

    /**
     * @param string $cipherText
     * @param string $key
     * @param string $iv
     * @param int $option
     * @param string|null $method
     *
     * @return string
     */
    public static function decrypt(string $cipherText, string $key, string $iv, int $option = OPENSSL_RAW_DATA, $method = null): string
    {
        self::validateKey($key);
        self::validateIv($iv);

        return openssl_decrypt($cipherText, $method ?: self::getMode(), $key, $option, $iv);
    }

    /**
     *
     * @return string
     */
    public static function getMode()
    {
        return 'aes-256-cbc';
    }

    /**
     * @param string $key
     */
    public static function validateKey(string $key)
    {
        if (!in_array(strlen($key), [32], true)) {
            throw new InvalidArgumentException(sprintf('Key length must be 32 bytes; got key len (%s).', strlen($key)));
        }
    }

    /**
     * @param string $iv
     *
     * @throws InvalidArgumentException
     */
    public static function validateIv(string $iv)
    {
        if (!empty($iv) && 16 !== strlen($iv)) {
            throw new InvalidArgumentException('IV length must be 16 bytes.');
        }
    }
}
