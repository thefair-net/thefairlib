<?php

namespace TheFairLib\Verify;

use TheFairLib\DB\Redis\Cache;

class Image
{

    //图片验证码
    CONST CACHE_NAME = "IMAGE_STANDARD_IMAGE_CODE_";
    //随机因子
    private $charset = [
        'mixture' => 'abcdefghkmnpstuvwxyzABCDEFGHKMNPRSTUVWXYZ23456789',
        'number' => '0123456789'
    ];
    //是否显示混合验证码
    private $mixture = false;
    //验证码
    private $code;
    //验证码长度
    private $codelen = 4;
    //宽度
    private $width = 85;
    //高度
    private $height = 42;
    //图形资源句柄
    private $img;
    //指定的字体
    private $font;
    //指定字体大小
    private $fontsize = 18;
    //指定字体颜色
    private $fontcolor;
    //设置背景色
    private $background = '#EDF7FE';
    //验证码类型
    public $type = '';
    //输出多少次后更换验证码
    private $testLimit = 3;
    //随机字体
    public $randFont = true;
    //随机字体大小
    public $randFontSize = true;

    public $cacheHost = 'default';

    //验证码有效期,单位:秒
    private $expired = 300;

    private $sessionKeyPrefix = '';

    static private $cache = null;

    //构造方法初始化
    public function __construct()
    {
        $this->font = $this->getFont();
        self::$cache = Cache::getInstance($this->cacheHost);
    }

    //魔术方法，设置
    public function __set($name, $value)
    {
        if (empty($name) || in_array($name, ['code', 'img'])) {
            return false;
        }
        $this->$name = $value;
    }

    //生成随机码
    private function createCode()
    {
        $code = '';
        $charset = $this->mixture ? $this->charset['mixture'] : $this->charset['number'];
        $_len = strlen($charset) - 1;
        for ($i = 0; $i < $this->codelen; $i++) {
            $code .= $charset[mt_rand(0, $_len)];
        }
        return $code;
    }

    //生成背景
    private function createBg()
    {
        $this->img = imagecreatetruecolor($this->width, $this->height);
        if (empty($this->background)) {
            $color = imagecolorallocate($this->img, mt_rand(157, 255), mt_rand(157, 255), mt_rand(157, 255));
        } else {
            //设置背景色
            $color = imagecolorallocate($this->img, hexdec(substr($this->background, 1, 2)), hexdec(substr($this->background, 3, 2)), hexdec(substr($this->background, 5, 2)));
        }
        imagefilledrectangle($this->img, 0, $this->height, $this->width, 0, $color);
    }

    //生成文字
    private function createFont()
    {
        $_x = $this->width / $this->codelen;
        $isFontColor = false;
        if ($this->fontcolor && !$isFontColor) {
            $this->fontcolor = imagecolorallocate($this->img, hexdec(substr($this->fontcolor, 1, 2)), hexdec(substr($this->fontcolor, 3, 2)), hexdec(substr($this->fontcolor, 5, 2)));
            $isFontColor = true;
        }

        if ($this->randFontSize) {
            $this->fontsize = $this->height * mt_rand(35, 45) / 100;
        }

        for ($i = 0; $i < $this->codelen; $i++) {
            if (!$isFontColor) {
                $this->fontcolor = imagecolorallocate($this->img, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            }
            imagettftext($this->img, $this->fontsize, mt_rand(-30, 50), $_x * $i + mt_rand(1, 5), $this->height / 1.4, $this->fontcolor, $this->font, $this->code[$i]);
        }
    }

    //生成线条、雪花
    private function createLine()
    {
        for ($i = 0; $i < 3; $i++) {
            $color = imagecolorallocate($this->img, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
            imageline($this->img, mt_rand(0, $this->width), mt_rand(0, $this->height), mt_rand(0, $this->width), mt_rand(0, $this->height), $color);
        }
        for ($i = 0; $i < 10; $i++) {
            $color = imagecolorallocate($this->img, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
            imagestring($this->img, mt_rand(1, 5), mt_rand(0, $this->width), mt_rand(0, $this->height), '$', $color);
        }
    }

    /**
     * 输出验证码，默认输出图片
     *
     * @param bool $regenerate
     * @param bool $base64
     * @return string
     */
    public function output($regenerate = false, $base64 = false)
    {
        if ($base64 === true) {
            ob_start();
            $this->createBg();
            $this->getVerifyCode($regenerate);
            $this->createLine();
            $this->createFont();
            imagepng($this->img);
            imagedestroy($this->img);
            $img = ob_get_flush();
            ob_end_clean();
            return base64_encode($img);
        }
        header('Pragma: public');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Content-Transfer-Encoding: binary');
        header('Content-type:image/png');
        $this->createBg();
        $this->getVerifyCode($regenerate);
        $this->createLine();
        $this->createFont();
        imagepng($this->img);
        imagedestroy($this->img);
        exit;
    }

    /**
     * 刷新验证码
     *
     * @param bool $regenerate 刷新
     * @return string
     */
    protected function getVerifyCode($regenerate = false)
    {
        $name = $this->getSessionKey();
        $old = self::$cache->get($name);
        //没有的话重新生成个
        if (empty($old) || $regenerate) {
            $this->code = $this->createCode();
            self::$cache->setex($name, $this->expired, $this->code);//保存验证码5分钟
        } else {
            $this->code = $old;
        }
        return $this->code;
    }

    //获取验证码
    public function getCode()
    {
        return strtolower($this->getVerifyCode());
    }

    /**
     * 验证输入，看它是否生成的代码相匹配。
     *
     * @param string $input 用户输入的验证码
     * @param bool $caseSensitive 是否验证大小写
     * @return boolean
     */
    public function validate($input, $caseSensitive = false)
    {
        $code = $this->getVerifyCode();

        $valid = $caseSensitive ? ($input === $code) : strcasecmp($input, $code) === 0;

        if ($valid) {
            //验证成功后删除缓存中的数据
            $name = $this->getSessionKey();
            self::$cache->del($name);
        }
        return $valid;
    }

    //返回用于存储验证码的会话变量名。
    protected function getSessionKey()
    {
        return $this->sessionKeyPrefix.md5(self::CACHE_NAME . $this->type . $_COOKIE['PHPSESSID']);
    }

    /**
     * 随机获得字体
     *
     * @return int|string
     */
    private function getFont()
    {
        $fileName = "1";
        $dirPath = realpath(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'font';
        if (!$this->randFont) return $dirPath . DIRECTORY_SEPARATOR . $fileName . ".ttf";
        $file = scandir($dirPath);//查看文件数量
        $count = count($file) - 2;
        if ($count > 1) $fileName = mt_rand(1, $count);
        $fileName = $dirPath . DIRECTORY_SEPARATOR . "{$fileName}.ttf";
        return $fileName;
    }
}
