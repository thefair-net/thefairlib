<?php
/**
 * TranslateHelper.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\I18n;

use TheFairLib\Config\Config;

class TranslateHelper
{
    /**
     * 默认语言
     *
     * @var string
     */
    static private $_lang = 'cn';

    /**
     * 设置当前显示语言
     *
     * @param $lang
     * @return mixed
     */
    static public function setLang($lang){
        return self::$_lang = $lang;
    }

    /**
     * 获取当前语言
     *
     * @return string
     */
    static public function getLang(){
        return self::$_lang;
    }

    /**
     * 翻译
     * 翻译的语言包配置到/app/root/path/config/i18n/$lang/
     *
     * @param string $type 针对语言包中的目录，比如api/error.php，对于type为api_error
     * @param string $label 具体的配置文件中的数值key，参考config使用方法
     * @param string $lang
     * @return bool
     */
    static public function translate($type, $label, $lang = ''){
        $funcName = 'get_i18n_'.(empty($lang) || !in_array($lang, ['cn', 'jp']) ? self::getLang() : $lang).'_'.$type;
        $ret = Config::$funcName($label);
        return empty($ret) ? false : $ret;
    }
}