<?php
/**
 * Utility.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */

namespace TheFairLib\Utility;

use TheFairLib\Config\Config;
use TheFairLib\Http\Cookie;

class Utility
{
    private static $registry = array();

    /**
     * 设置全局静态变量，方便在module、controller调用
     *
     * @param $func
     * @param $arguments
     * @return bool|mixed
     */
    public static function __callStatic($func, $arguments)
    {

        $funcAry = explode('_', $func);
        if (empty($funcAry)) {
            return false;
        }
        $type = current($funcAry);
        array_shift($funcAry);
        $key = implode('_', $funcAry);

        if (!in_array($type, array('set', 'get')) || $key == '') {
            return false;
        }

        switch ($type) {
            case 'set':
                self::arraySet(self::$registry, $key . (!empty($arguments[1]) ? '.' . $arguments[1] : ''), $arguments[0]);
                return self::arrayGet(self::$registry, $key . (!empty($arguments[1]) ? '.' . $arguments[1] : ''), false);
                break;
            case 'get':
                return self::arrayGet(self::$registry, $key . (!empty($arguments[0]) ? '.' . $arguments[0] : ''), false);
                break;
            default:
        }

        return null;
    }

    /**
     * 以“.”为分隔符获取多维数组的值
     *
     * @param $array
     * @param $key
     * @param null $default
     * @return mixed
     */
    public static function arrayGet($array, $key, $default = null)
    {
        if (is_null($key)) return $array;

        if (isset($array[$key])) return $array[$key];

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return value($default);
            }

            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * 以“.”为分隔符设置多维数组的值
     *
     * @param $array
     * @param $key
     * @param $value
     * @return mixed
     */
    public static function arraySet(&$array, $key, $value)
    {
        if (is_null($key)) return $array = $value;

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            if (!isset($array[$key]) || !is_array($array[$key])) {
                $array[$key] = array();
            }

            $array =& $array[$key];
        }

        $array[array_shift($keys)] = $value;

        return $array;
    }

    /**
     * 获取当前服务器环境配置
     *
     * @return string
     */
    public static function getPhase()
    {
        $phase = \Config::get('app.phase');
        if ($phase != 'staging')
            $phase = '';

        return $phase;
    }

    /**
     * 加密
     * @static
     * @param $data
     * @return string
     */
    public static function Encrypt($data, $key)
    {
        $cipher = MCRYPT_TRIPLEDES;
        $modes = MCRYPT_MODE_ECB;

        # Add PKCS7 padding.
        $block = mcrypt_get_block_size($cipher, $modes);
        $pad = $block - (strlen($data) % $block);
        $data .= str_repeat(chr($pad), $pad);

        $iv = mcrypt_create_iv(mcrypt_get_iv_size($cipher, $modes), MCRYPT_RAND);
        $encrypted = @mcrypt_encrypt($cipher, $key, $data, $modes, $iv);

        return base64_encode($encrypted);
    }

    /**
     * 解密
     * @static
     * @param $data
     * @return string
     */
    public static function Decrypt($data, $key)
    {
        $cipher = MCRYPT_TRIPLEDES;
        $modes = MCRYPT_MODE_ECB;

        $iv = mcrypt_create_iv(mcrypt_get_iv_size($cipher, $modes), MCRYPT_RAND);
        $data = @mcrypt_decrypt($cipher, $key, base64_decode($data), $modes, $iv);

        # Strip padding out.
        $block = mcrypt_get_block_size($cipher, $modes);
        $pad = ord($data[($len = strlen($data)) - 1]);
        $decrypted = substr($data, 0, strlen($data) - $pad);

        return $decrypted;
    }

    /**
     * 获取 $_POST,$_GET,$_REQUEST,$_COKIE,$_SERVER 获取变量
     *
     * @param $var
     * @param string $target
     * @param string $type
     * @param string $default
     * @return array|float|int|string
     */
    public static function getGpc($var, $target = 'R', $type = 'string', $default = NULL)
    {
        switch (strtoupper($target)) {
            case 'R':
                $super = &$_REQUEST;
                break;
            case 'P':
                $super = &$_POST;
                break;
            case 'S':
                $super = &$_SERVER;
                break;
            case 'C':
                $super = &$_COOKIE;
                break;
            case 'G':
                $super = &$_GET;
                break;
            default:
                $super = &$_GET;
                $type = $target;
                break;
        }
        switch ($type) {
            case 'int':
                $value = isset($super[$var]) ? intval($super[$var]) : 0;
                break;
            case 'float':
                $value = isset($super[$var]) ? floatval($super[$var]) : 0;
                break;
            case 'bigint':
                $value = isset($super[$var]) ? sprintf('%.0f', $super[$var]) : 0;
                break;
            case 'array':
                $value = isset($super[$var]) ? self::dfsArray($super[$var]) : array();
                break;
            case 'original':
                $value = isset($super[$var]) ? $super[$var] : '';
                break;
            case 'string':
            default:
                $value = isset($super[$var]) ? htmlspecialchars($super[$var]) : '';
                $value = mb_check_encoding($value, 'UTF-8') ? $value : utf8_encode($value);
                break;
        }

        return !empty($value) ? $value : $default;
    }

    /**
     * 深度处理数组
     *
     * @param $arr
     * @return array
     */
    public static function dfsArray($arr)
    {
        $ret = array();
        if (!empty($arr) && is_array($arr)) {
            foreach ($arr as $k => $v) {
                if (is_array($v)) {
                    $ret[$k] = self::dfsArray($v);
                } else if (is_numeric($v)) {
                    $ret[$k] = $v;
                } else/* if(is_string($v)) */ {
                    $ret[$k] = htmlspecialchars($v);
                }
            }
        }

        return $ret;
    }

    /**
     * 获取用户IP
     *
     * @return string
     */
    public static function getUserIp()
    {
        $x_real_ip = empty($_SERVER['HTTP_X_REAL_IP']) ? '' : $_SERVER['HTTP_X_REAL_IP'];
        if (!empty($x_real_ip)) {
            $ips = explode(',', $x_real_ip);
            $client_ip = trim($ips[0]);
        } else {
            $client_ip = $_SERVER['REMOTE_ADDR'];
        }
        return $client_ip;
    }

    /**
     * @param $string
     * @return bool
     */
    public static function isStringEncodeWithUTF8MB4($string)
    {
        $len = mb_strlen($string, 'utf-8');

        for ($i = 0; $i < $len; $i++) {
            $str = mb_substr($string, $i, 1, 'utf-8');

            if (ord($str) >= 240) {
                return true;
            }
        }

        return false;
    }

    /**
     * 设置COOKIE
     * @param $key
     * @param $value
     * @param $ttl
     */
    public static function setResponseCookie($key, $value, $ttl)
    {
        $cookie = self::getResponseCookie();
        if(empty($cookie)){
            $cookie = [];
        }
        $domainConf = Config::get_app('cookie.default_domain');
        $path = '/';
        $domain = !empty($domainConf) ? $domainConf : '';

        $cookie[] = new Cookie($key, $value, $ttl, $path, $domainConf);
        //@todo
        self::set_reponse_cookie($cookie);
    }

    public static function getResponseCookie(){
        return self::get_reponse_cookie();
    }

    /**
     * 返回分辨率相关信息
     *
     * @param string $key
     * @return array
     */
    public static function getResolution($key = '')
    {
        $resolution = array();
        $cookie = self::getGpc('resolution', 'C');
        if (!empty($cookie)) {
            $tmpResolution = explode("*", $cookie);
            $resolution = array(
                'resolution' => $cookie,
                'width' => $tmpResolution[0],
                'height' => $tmpResolution[1],
            );
        }

        return !empty($key) ? (!empty($resolution[$key]) ? $resolution[$key] : null) : $resolution;
    }


    /**
     * 字符串长度（UTF-8编码下字符串长度.中文）
     *
     * @param string $str 字符串.
     *
     * @return number
     */
    public static function utf8StrLen($str = null)
    {
        $count = 0;
        for ($i = 0; $i < strlen($str); $i++) {
            $value = ord($str[$i]);
            if ($value > 127) {
                $count++;
                if ($value >= 192 && $value <= 223)
                    $i++;
                elseif ($value >= 224 && $value <= 239)
                    $i = $i + 2;
                elseif ($value >= 240 && $value <= 247)
                    $i = $i + 3;
                else
                    return 0;
            }
            $count++;
        }
        return $count;
    }

    /**
     * 字符串截取，支持中文和其他编码
     * @static
     * @access public
     * @param string $str  需要转换的字符串
     * @param int $start  开始位置
     * @param string $length 截取长度
     * @param string $charset 编码格式
     * @param bool $suffix 截断显示字符
     * @return string
     */
    public static function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
    {
        if (function_exists("mb_substr"))
            $slice = mb_substr($str, $start, $length, $charset);
        elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
        } else {
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("", array_slice($match[0], $start, $length));
        }
        return $suffix ? $slice . '...' : $slice;
    }

    /**
     * 验证正确的手机号
     *
     * @param $mobile
     * @return int
     */
    public static function isMobile($mobile)
    {
        return preg_match('/^(0|86|17951)?(13[0-9]|15[012356789]|17[678]|18[0-9]|14[57])[0-9]{8}$/', $mobile);
    }
}
