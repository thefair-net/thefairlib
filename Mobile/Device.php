<?php
/**
 * Device.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
class Device{
    static public $deviceInfo = array();
    /**
     * @var Device
     */
    static protected $Instance;

    /**
     * 客户端名称（区分聚美商城、海淘、青春、母婴）
     *
     * @var string
     */
    private $_appName = '';

    /**
     * 客户端包名
     *
     * @var string
     */
    private $_appId = '';

    /**
     * 客户端包密钥
     *
     * @var string
     */
    private $_appSecret = '';

    /**
     * 客户端唯一标识
     *
     * @var string
     */
    private $_appDeviceId = '';

    /**
     * 客户端自定义链接前缀
     *
     * @var string
     */
    private $_appUrlSchemePrefix = '';

    /**
     * 客户端平台
     *
     * @var string
     */
    private $_appPlatform = '';

    /**
     * 客户端版本号
     *
     * @var string
     */
    private $_appClientV = '';

    /**
     * @return Device
     */
    static public function Instance()
    {
        if (empty(self::$Instance)) {
            self::$Instance = new Device();
        }
        return self::$Instance;
    }

    public function __construct(){
        $this->_initAppInfo();
    }

    /**
     * 客户端名称（区分聚美商城、海淘、青春、母婴）
     *
     * @return string
     */
    public function getAppName(){
        return $this->_appName;
    }

    /**
     * 获取客户端包名
     *
     * @return string
     */
    public function getAppId(){
        return $this->_appId;
    }

    /**
     * 获取客户端密钥
     *
     * @return string
     */
    public function getAppSecret(){
        return $this->_appSecret;
    }

    /**
     * 获取客户端uuid
     *
     * @return string
     */
    public function getAppDeviceId(){
        return $this->_appDeviceId;
    }

    /**
     * 获取客户端urlscheme前缀
     *
     * @return string
     */
    public function getAppUrlSchemePrefix(){
        return $this->_appUrlSchemePrefix;
    }

    /**
     * 获取客户端平台信息
     *
     * @return string
     */
    public function getAppPlatform(){
        return $this->_appPlatform;
    }

    /**
     * 获取客户端版本信息
     *
     * @return string
     */
    public function getAppClientV(){
        return $this->_appClientV;
    }

    /**
     * 初始化客户端信息
     */
    private function _initAppInfo(){
        $this->_parseAppUA();
        $this->_appId       = $this->_getAppIdFromRequest();
        $this->_appSecret   = $this->_getAppSecretFromConf($this->_appId);
        $this->_appPlatform = self::$deviceInfo['platform'];
        $this->_appClientV  = self::$deviceInfo['client_v'];
        $this->_appDeviceId = self::$deviceInfo['device_id'];
        $this->_appUrlSchemePrefix = $this->_getAppUrlSchemePrefixConf();
    }

    /**
     * 获取客户端包名设置
     *
     * @param string $paltform
     * @return array
     */
    private function _getAppIdConf($paltform = ''){
        $appidList  = array(
            'iphone' => array(
                'com.jumei.iphone'      => array('name' => self::APP_NAME_JUMEI, 'default' => true),
                'com.jumei.global'      => array('name' => self::APP_NAME_GLOBAL),
                'com.jumei.youth'       => array('name' => self::APP_NAME_YOUTH),
                'com.jumei.baby'        => array('name' => self::APP_NAME_BABY),
                //测试包
                'com.jumei.test'        => array('name' => self::APP_NAME_JUMEI),
                'com.jumei.globaltest'  => array('name' => self::APP_NAME_GLOBAL),
                'com.jumei.youthtest'   => array('name' => self::APP_NAME_YOUTH),
            ),
            'android' => array(
                'com.jm.android.jumei'  => array('name' => self::APP_NAME_JUMEI, 'default' => true),
                'com.jm.android.global' => array('name' => self::APP_NAME_GLOBAL),
                'com.jm.android.youth'  => array('name' => self::APP_NAME_YOUTH),
                'com.jm.android.baby'   => array('name' => self::APP_NAME_BABY),
            ),
            'ipad' => array(
                'com.jumei.mallhd'      => array('name' => self::APP_NAME_JUMEI, 'default' => true),
                'com.jumei.globalmallhd'=> array('name' => self::APP_NAME_GLOBAL),
                'com.jumei.youthmallhd' => array('name' => self::APP_NAME_YOUTH),
            ),
        );

        return !empty($paltform) ? (!empty($appidList[$paltform]) ? $appidList[$paltform] : array()) : array();
    }

    /**
     * 获取客户端urlscheme的前缀设置
     *
     * @param string $appName
     * @return array|string
     */
    private function _getAppUrlSchemePrefixConf($appName = ''){
        return 'thefair://';
    }

    /**
     * 从请求中获取客户端的包名
     *
     * @return string
     */
    private function _getAppIdFromRequest(){
        return strtolower(htmlspecialchars(!empty($_COOKIE['appid']) ? $_COOKIE['appid'] : (!empty($_REQUEST['appid']) ? $_REQUEST['appid'] : '')));
    }

    /**
     * 从请求中获取客户端的包名
     *
     * @return string
     */
    private function _getAppSecretFromConf($appId){
        return TheFairLib\Config\Config::get_auth(self::$deviceInfo['platform'].'.'.$appId.'.secret');
    }


    private function _parseAppUA(){
        if (isset($_SERVER['HTTP_X_TAOO_UA'])){
            $ua = $_SERVER['HTTP_X_TAOO_UA'];
        }else{
            $ua = 'h5/1.0 (h5;h5;1.0;cn;wap;1.0;cn;h5;unkonwn) h5/1.0';
        }
        preg_match('/^(?<product>.*?)\/(?<version>.*?) \((?<device_info>.*?)\) (?<render_info>.*?)$/i', $ua, $matches);

        list($deviceLabel, $deviceOs, $deviceOsVersion, $deviceLang, $platform, $clientV, $site, $source, $deviceId) = explode(';', $matches['device_info']);
        self::$deviceInfo = array(
            'platform' => $platform,
            'client_v'  => $clientV,
            'site'      => $site,
            'source'    => $source,
            'device_id' => $deviceId,
            'lang'      => $deviceLang,
        );
        //<device_os>\w+);(?<device_os_version>\w+);(?<os_lang>\w+);(?<platform>\w+);(?<client_v>\w+);(?<site>\w+);(?<source>\w+);(?<device_id>

    }

    public function getDeviceInfo(){
        return self::$deviceInfo;
    }
}