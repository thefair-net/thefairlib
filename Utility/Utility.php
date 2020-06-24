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
use Endroid\QrCode\QrCode;

class Utility
{
    private static $registry = [];

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

    public static function clearRegistry()
    {
        self::$registry = [];
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
        $phase = Config::get('app.phase');
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
        $x_real_ip = empty($_SERVER['HTTP_X_FORWARDED_FOR']) ? '' : $_SERVER['HTTP_X_FORWARDED_FOR'];
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
     * @param $domain
     */
    public static function setResponseCookie($key, $value, $ttl, $domain = '')
    {
        $cookie = self::getResponseCookie();
        if (empty($cookie)) {
            $cookie = [];
        }

        $path = '/';
        if (empty($domain)) {
            $host = $_SERVER['HTTP_HOST'];

            if (strpos($host, '.rd.taooo.cc') !== false) {
                $domain = '.rd.taooo.cc';
            } elseif (strpos($host, '.taooo.cc') !== false) {
                $domain = '.taooo.cc';
            } elseif (strpos($host, '.rd.molandapp.com') !== false) {
                $domain = '.rd.molandapp.com';
            } elseif (strpos($host, '.molandapp.com') !== false) {
                $domain = '.molandapp.com';
            } elseif (strpos($host, 'intra.api.localdomain') !== false) {
                $domain = '.taooo.cc';
            } elseif (strpos($host, 'intra.api.molandapp.com.localdomain') !== false) {
                $domain = '.molandapp.com';
            } else {
                $domainConf = Config::get_app('cookie.default_domain');
                $domain = !empty($domainConf) ? $domainConf : '';
            }
        }

        $cookie[] = new Cookie($key, $value, $ttl, $path, $domain);
        //@todo
        self::set_reponse_cookie($cookie);
    }

    public static function getResponseCookie()
    {
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
     * html代码截断, html代码将被视为零宽, 中文字符/标点宽度为1, 英文字符/标点宽度为0.5
     *
     * @param string $input
     * @param int $length >0
     * @param bool $ignore_html
     * @param string $padding
     * @return string
     */
    public static function utf8SubStr($input, $length, $ignore_html = true, $padding = '...')
    {
        $strlen = strlen($input);
        if ($strlen <= $length) return $input;

        $selfclosing = array('br', 'img', 'hr', 'base', 'meta', 'area', 'input'); // 不考虑注释<!--
        $pos = $width = 0;
        $tag_stack = array();
        while ($pos < $strlen && $width < $length - 0.5) {
            $prechar = ord($input{$pos});
            if ($prechar < 128) { //单字节
                if ($ignore_html && $prechar == 60) {// <
                    $spacepos = strpos($input, ' ', $pos);
                    $closepos = strpos($input, '>', $pos);
                    $tagpos = $spacepos === false ? $closepos : min($spacepos, $closepos);
                    if ($tagpos == false) {
                        $pos_length = 1;
                        $word_length = 0.5;
                    } else {
                        if ($input{$pos + 1} == '/') { // 出栈
                            // 不考虑标签错位闭合情况:<a><b></a></b>
                            array_pop($tag_stack);
                        } else { // 入栈
                            $tag = substr($input, $pos + 1, $tagpos - $pos - 1);
                            if (!in_array($tag, $selfclosing)) {
                                array_push($tag_stack, $tag);
                            }
                        }
                        $endpos = strpos($input, '>', $pos);
                        if ($endpos === false) {
                            $pos_length = 1;
                            $word_length = 0.5;
                        } else {
                            $pos_length = $endpos - $pos + 1;
                            $word_length = 0;
                        }
                    }
                } else if ($prechar == '38') { // &
                    $semipos = strpos($input, ';', $pos);
                    /*
                     * &#1234; 应该是占一个汉字的宽度
                     * &nbsp; 占半个汉字宽度,
                     * todo区分
                     */
                    if ($semipos !== FALSE && $semipos - $pos < 6) { // &#1234; 最多支持6字节长的
                        $pos_length = $semipos - $pos + 1;
                        $word_length = 0.5;
                    } else {
                        $pos_length = 1;
                        $word_length = 0.5;
                    }
                } else {
                    $pos_length = 1;
                    $word_length = 0.5;
                }
            } else {
                $word_length = 1;
                if ($prechar < 192) {
                    $pos_length = 1;//error
                } elseif ($prechar < 224) {
                    $pos_length = 2;
                } elseif ($prechar < 240) {
                    $pos_length = 3;
                } elseif ($prechar < 248) {
                    $pos_length = 4;
                } elseif ($prechar < 252) {
                    $pos_length = 5;
                } else {
                    $pos_length = 6;
                }
            }

            $pos += $pos_length;
            $width += $word_length;
        }
        $return = $pos < $strlen ? substr($input, 0, $pos) . $padding : $input;
        if (!empty($tag_stack)) {
            while (!empty($tag_stack)) {
                $tag = array_pop($tag_stack);
                $return .= "</$tag>";
            }
        }
        return $return;
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
     * @param string $str 需要转换的字符串
     * @param int $start 开始位置
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
        return preg_match('/^(0|86|17951)?(13[0-9]|14[57]|15[0-9]|17[0-9]|16[0-9]|18[0-9]|19[0-9])[0-9]{8}$/', $mobile);
    }

    /**
     * 过滤空格
     *
     * @param $str
     * @return mixed
     */
    public static function trim($str)
    {
        $search = array(" ", "　", "\n", "\r", "\t");
        $replace = array("", "", "", "", "");
        return str_replace($search, $replace, $str);
    }

    /**
     * 用于随机抽奖
     * 例如:
     * $check = Utility::getRandomLottery(['Y' => 6, 'N' => 4]);
     * $check的值为Y/N,$proArr为随机几率
     *
     * @param $proArr
     * @return int|string
     */
    public static function getRandomLottery($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);             //抽取随机数
            if ($randNum <= $proCur) {
                $result = $key;                         //得出结果
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset ($proArr);
        return $result;
    }

    /**
     * 时间戳格式转换
     *
     * @param $timestamp
     * @return bool|string
     */
    public static function formatTimestamp($timestamp)
    {
        $nowTime = time();
        $showTime = is_numeric($timestamp) ? $timestamp : strtotime($timestamp);
        $dur = $nowTime - $showTime;
        if ($dur < 180) {
            return '刚刚';
        } else {
            if ($dur < 3600) {
                return floor($dur / 60) . '分钟前';
            } else {
                if ($dur < 86400) {
                    return floor($dur / 3600) . '小时前';
                } else {
                    return date("n月j日 H:i", $showTime);
//                    if($dur < 864000){
//                        return floor($dur/86400).'天前';
//                    }else{
//                        return date("Y-m-d", $showTime);
//                    }
                }
            }
        }
    }

    /**
     * 统一封装的encode方法
     *
     * @param $data
     * @param string $format
     * @return string
     */
    public static function encode($data, $format = 'json')
    {
        switch ($format) {
            case 'json':
                if (extension_loaded('jsond')) {
                    $ret = jsond_encode($data, JSON_UNESCAPED_UNICODE);
                } else {
                    $ret = json_encode($data, JSON_UNESCAPED_UNICODE);
                }
                break;
            case 'base64':
                $ret = base64_encode($data);
                break;
            case 'serialize':
                $ret = serialize($data);
                break;
            default:
                $ret = $data;

        }

        return $ret;
    }

    /**
     * 统一封装的decode方法
     *
     * @param $data
     * @param string $format
     * @return mixed|string
     */
    public static function decode($data, $format = 'json')
    {
        switch ($format) {
            case 'json':
                if (extension_loaded('jsond')) {
                    $ret = jsond_decode($data, true);
                } else {
                    $ret = json_decode($data, true);
                }
                break;
            case 'base64':
                $ret = base64_decode($data);
                break;
            case 'serialize':
                $ret = unserialize($data);
                break;
            default:
                $ret = $data;

        }

        return $ret;
    }

    /**
     * 分词,需要引入 "lmz/phpanalysis": "*"
     *
     * @param $str
     * @return array
     */
    public static function participle($str)
    {
        \Phpanalysis\Phpanalysis::$loadInit = false;
        $pa = new \Phpanalysis\Phpanalysis('utf-8', 'utf-8', true);
        //载入词典
        $pa->LoadDict();
        //执行分词
        $pa->SetSource($str);
        $pa->differMax = true;
        $pa->unitWord = true;
        $pa->StartAnalysis(true);
        $result = $pa->GetFinallyResult('###', false);
        $data = [];
        if (!empty($result)) {
            $data = explode('###', $result);
        }
        return $data;
    }

    /**
     * 生成二维码
     *
     * @param $content
     * @param int $setSize
     * @param int $padding
     * @param bool $base64
     * @param string $logoPath
     * @param array $foregroundColor
     * @param array $backgroundColor
     * @return string
     */
    public static function qrCode($content, $setSize = 300, $padding = 0, $base64 = false, $logoPath = '', $foregroundColor = [], $backgroundColor = [])
    {
        $qrCode = new QrCode();
        if (empty($foregroundColor)) {
            $foregroundColor = ['r' => 0, 'g' => 0, 'b' => 0, 'a' => 0];
        }
        if (empty($backgroundColor)) {
            $backgroundColor = ['r' => 255, 'g' => 255, 'b' => 255, 'a' => 0];
        }
        $qrCode
            ->setText($content)
            ->setExtension('png')
            ->setSize($setSize)
            ->setPadding($padding)
            ->setErrorCorrection('high')
            ->setForegroundColor($foregroundColor)
            ->setBackgroundColor($backgroundColor)
            ->setLabelFontSize(16)
            ->setImageType(QrCode::IMAGE_TYPE_PNG);
        if (!empty($logoPath) && file_exists($logoPath)) {
            $qrCode->setLogo($logoPath);
        }
        $data = $qrCode->get();
        if ($base64) {
            $data = 'data:image/png;base64,' . base64_encode($data);
        }
        return $data;
    }

    /**
     * 取得根域名
     * @param $domain //域名
     * @param array $domain_postfix_cn_array
     * @return string 返回根域名
     */
    public static function getUrlToDomain($domain, $domain_postfix_cn_array = ["com", "net", "org", "gov", "edu", "com.cn", "cn", "cc"])
    {
        $array_domain = explode(".", $domain);
        $array_num = count($array_domain) - 1;
        if ($array_domain[$array_num] == 'cn') {
            if (in_array($array_domain[$array_num - 1], $domain_postfix_cn_array)) {
                $re_domain = $array_domain[$array_num - 2] . "." . $array_domain[$array_num - 1] . "." . $array_domain[$array_num];
            } else {
                $re_domain = $array_domain[$array_num - 1] . "." . $array_domain[$array_num];
            }
        } else {
            $re_domain = $array_domain[$array_num - 1] . "." . $array_domain[$array_num];
        }
        return $re_domain;
    }
}
