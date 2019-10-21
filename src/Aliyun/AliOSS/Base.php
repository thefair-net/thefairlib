<?php
namespace TheFairLib\Aliyun\AliOSS;

//关于endpoint的介绍见, endpoint就是OSS访问的域名
use \TheFairLib\Aliyun\AliOSS\util\OSS_Exception;
use \TheFairLib\Config\Config;
use TheFairLib\I18n\TranslateHelper;

//设置默认时区
date_default_timezone_set('Asia/Shanghai');

//检测API路径
if (!defined('OSS_API_PATH'))
    define('OSS_API_PATH', dirname(__FILE__));

//加载conf.inc.php文件,里面保存着OSS的地址以及用户访问的ID和KEY
//$lang = TranslateHelper::getLang();
//$config = Config::get_aliyun();
//if (empty($lang)) {
//    $config = $config[$lang]['OSS'];
//} else {
//    $config = $config['cn']['OSS'];
//}
//define('OSS_ACCESS_ID', $config['OSS_ACCESS_ID']);
//define('OSS_ACCESS_KEY', $config['OSS_ACCESS_KEY']);
//define('OSS_ENDPOINT', $config['OSS_ENDPOINT']);
//define('OSS_TEST_BUCKET', $config['OSS_TEST_BUCKET']);

//是否记录日志
define('ALI_LOG', false);

//自定义日志路径，如果没有设置，则使用系统默认路径，在./logs/
//define('ALI_LOG_PATH','');

//是否显示LOG输出
define('ALI_DISPLAY_LOG', false);

//语言版本设置
define('ALI_LANG', 'zh');

//检测语言包
if (file_exists(OSS_API_PATH . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . ALI_LANG . '.inc.php')) {
    require_once OSS_API_PATH . DIRECTORY_SEPARATOR . 'lang' . DIRECTORY_SEPARATOR . ALI_LANG . '.inc.php';
} else {
    throw new OSS_Exception(OSS_LANG_FILE_NOT_EXIST);
}

//定义软件名称，版本号等信息
define('OSS_NAME', 'aliyun-oss-sdk-php');
define('OSS_VERSION', '1.1.7');
define('OSS_BUILD', '20150311');
define('OSS_AUTHOR', 'xiaobing');

//检测get_loaded_extensions函数是否被禁用。由于有些版本把该函数禁用了，所以先检测该函数是否存在。
if (function_exists('get_loaded_extensions')) {
    //检测curl扩展
    $enabled_extension = array("curl");
    $extensions = get_loaded_extensions();
    if ($extensions) {
        foreach ($enabled_extension as $item) {
            if (!in_array($item, $extensions)) {
                throw new OSS_Exception("Extension {" . $item . "} has been disabled, please check php.ini config");
            }
        }
    } else {
        throw new OSS_Exception(OSS_NO_ANY_EXTENSIONS_LOADED);
    }
} else {
    throw new OSS_Exception('Function get_loaded_extensions has been disabled, please check php config.');
}


class Base
{

    static public $instance;

    /**
     * @return Base
     */
    static public function Instance()
    {
        $class = get_called_class();
        if (empty(self::$instance[$class])) {
            self::$instance[$class] = new $class();
        }
        return self::$instance[$class];
    }

    /**
     * ALIOSSSDK
     *
     * @param string $label
     * @return mixed
     */
    public function getALIOSSSDK($label = 'OSS')
    {
        if (empty(self::$instance['ALIOSSSDK'])) {

            $config = Config::get_aliyun();
            $lang = TranslateHelper::getLang();
            if (empty($lang)) {
                $config = $config[$lang];
            } else {
                $config = $config['cn'];
            }

            define('OSS_ACCESS_ID', $config[$label]['OSS_ACCESS_ID']);
            define('OSS_ACCESS_KEY', $config[$label]['OSS_ACCESS_KEY']);
            define('OSS_ENDPOINT', $config[$label]['OSS_ENDPOINT']);
            define('OSS_TEST_BUCKET', $config[$label]['OSS_TEST_BUCKET']);

            self::$instance['ALIOSSSDK'] = new ALIOSSSDK(OSS_ACCESS_ID, OSS_ACCESS_KEY, OSS_ENDPOINT);
        }
        return self::$instance['ALIOSSSDK'];
    }

    public function getBucketName()
    {
        return OSS_TEST_BUCKET;
    }


}
