<?php
/**
 * Device.php
 * 关于手机设备的特殊处理
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Mobile;

class Device
{
    /**
     * 聚美商城包名标识
     */
    const APP_NAME_JUMEI = 'jumei';
    /**
     * 海淘版包名标识
     */
    const APP_NAME_GLOBAL = 'global';
    /**
     * 母婴版包名标识
     */
    const APP_NAME_BABY = 'baby';
    /**
     * 青春版包名标识
     */
    const APP_NAME_YOUTH = 'youth';

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
    private $_appUuid = '';

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
    public function getAppUuid(){
        return $this->_appUuid;
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
        $this->_appId       = $this->_getAppIdFromRequest();
        $this->_appSecret   = $this->_getAppSecretFromRequest();
        $this->_appPlatform = $this->_getPlatformFromRequest();
        $this->_appClientV  = $this->_getClientVFromRequest();
        $this->_appUuid     = $this->_getUuidFromRequest();
        $appIdConf          = $this->_getAppIdConf($this->_appPlatform);
        if(!empty($appIdConf)){
            if(!empty($this->_appId)){
                if(empty($appIdConf[$this->_appId])){
                    $this->_appName = '';
                }else{
                    $this->_appName = $appIdConf[$this->_appId]['name'];
                }
            }else{
                if(!empty($appIdConf)){
                    foreach($appIdConf as $appId => $conf){
                        if($conf['default'] === true){
                            $this->_appId   = $appId;
                            $this->_appName = $conf['name'];
                            break;
                        }
                    }
                }
            }
        }
        if(empty($this->_appName)){
            $this->_appName = self::APP_NAME_JUMEI;
        }

        $this->_appUrlSchemePrefix = $this->_getAppUrlSchemePrefixConf($this->_appName);
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
        $conf = array(
            self::APP_NAME_JUMEI    => 'jumeimall',
            self::APP_NAME_GLOBAL   => 'jmglobal',
            self::APP_NAME_YOUTH    => 'jmyouth',
            self::APP_NAME_BABY     => 'jmbaby',
        );

        return !empty($appName) ? (!empty($conf[$appName]) ? $conf[$appName] : '') : $conf;
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
    private function _getAppSecretFromRequest(){
        return strtolower(htmlspecialchars(!empty($_COOKIE['appsecret']) ? $_COOKIE['appsecret'] : (!empty($_REQUEST['appsecret']) ? $_REQUEST['appsecret'] : '')));
    }

    /**
     * 从请求中获取客户端平台信息
     *
     * @return string
     */
    private function _getPlatformFromRequest(){
        return htmlspecialchars(!empty($_COOKIE['platform']) ? $_COOKIE['platform'] : (!empty($_REQUEST['platform']) ? $_REQUEST['platform'] : ''));
    }

    /**
     * 从请求中获取客户端版本号信息
     *
     * @return string
     */
    private function _getClientVFromRequest(){
        return htmlspecialchars(!empty($_COOKIE['client_v']) ? $_COOKIE['client_v'] : (!empty($_REQUEST['client_v']) ? $_REQUEST['client_v'] : ''));
    }

    /**
     * 从请求中获取客户端uuid信息
     *
     * @return string
     */
    private function _getUuidFromRequest(){
        if($this->_appPlatform == 'android'){
            return htmlspecialchars(!empty($_COOKIE['imei']) ? $_COOKIE['imei'] : '');
        }else{
            return htmlspecialchars(!empty($_COOKIE['idfa']) ? $_COOKIE['idfa'] : '');
        }

    }
}