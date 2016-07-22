<?php
/**
 * Client.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\SSO;

use TheFairLib\Config\Config;
use TheFairLib\Utility\Utility;

class Client
{
    static public $instance;

    private static $_cookieDomain = '';
    private static $_secret;
    private static $_accountCookieKey;
    private static $_tokenCookieKey;

    /**
     * @param array $customConfig
     * @return Client
     */
    static public function Instance($customConfig = [])
    {
        if (empty(self::$instance)) {
            self::$instance = new self($customConfig);
        }
        return self::$instance;
    }

    public function __construct($customConfig = [])
    {
        $systemConfig = (array)Config::get_sso('client');
        $config = array_merge($systemConfig, $customConfig);

        self::_checkConfig($config);
    }

    private static function _checkConfig($config)
    {
        $checkList = [
            '_cookieDomain' => 'cookie_domain',
            '_accountCookieKey' => 'account_cookie_key',
            '_tokenCookieKey' => 'token_cookie_key',
            '_secret' => 'secret',
        ];

        foreach ($checkList as $key => $confKey) {
            if (!empty($config[$confKey])) {
                self::${$key} = $config[$confKey];
            } else {
                throw new Exception('config error:' . $confKey);
            }
        }
    }

    public function checkLogin()
    {
        $checkRet = true;
        $token = Utility::getGpc(self::$_tokenCookieKey, 'C');
        $account = Utility::getGpc(self::$_accountCookieKey, 'C');

        if (!empty($account)) {
            $fields = $this->_getDecryptAccount($account);
            if (count($fields) == 6) {
                $checkRet = false;
            }

            list($uid, $md5Mobile, $nick, $md5Password, $state, $serverTk) = $fields;
            if ($token != $serverTk) {
                $checkRet = false;
            }
        } else {
            $checkRet = false;
        }

        return $checkRet;
    }

    public function doLogin($userInfo, $autoSetCookie = true, $keepLoginStatus = true)
    {
        $token = $this->_getToken();
        $ttl = $this->_getCookieTtl($keepLoginStatus);
        $uid = $userInfo['uid'];
        $account = $this->_getEncryptAccount($uid, $userInfo['mobile'], $userInfo['nick'], $userInfo['password'], $userInfo['state'], $token);
        $cookies = [
            [self::$_tokenCookieKey, $token, $ttl],
            [self::$_accountCookieKey, $account, $ttl],
            ['uid', $uid, $ttl],
        ];


        foreach ($cookies as $cookie) {
            list($K, $v, $t) = $cookie;
            $_COOKIE[$K] = $v;
            if ($autoSetCookie === true) {
                Utility::setResponseCookie($K, $v, $t, self::$_cookieDomain);
            }
        }

        $this->_setCurrentUid($uid);

        return $cookies;
    }

    public function doLogout($autoSetCookie = true)
    {
        $now = time();
        $ttl = $now - 10000;
        $cookies = [
            [self::$_tokenCookieKey, '', $ttl],
            [self::$_accountCookieKey, '', $ttl],
            ['uid', '', $ttl],
        ];

        foreach ($cookies as $cookie) {
            list($K, $v, $t) = $cookie;
            unset($_COOKIE[$K]);
            if ($autoSetCookie === true) {
                Utility::setResponseCookie($K, $v, $t, self::$_cookieDomain);
            }
        }

        $this->_setCurrentUid(0);

        return $cookies;
    }

    public function getCurrentUid()
    {
        return Utility::get_uid();
    }

    private function _setCurrentUid($uid)
    {
        Utility::set_uid($uid);
    }

    private function _getCookieTtl($keepLoginStatus = true)
    {
        $now = time();
        return $keepLoginStatus ? $now + 86400 * 30 : $now + 86400;
    }

    private function _getEncryptAccount($uid, $mobile, $nick, $md5Password, $state, $token)
    {
        return Utility::Encrypt(implode('|', [$uid, md5($mobile), $nick, $md5Password, $state, $token]), self::$_secret);
    }

    private function _getDecryptAccount($account)
    {
        return explode('|', Utility::Decrypt($account, self::$_secret));
    }

    private function _getToken()
    {
        $time = time();
        $token = sha1($time);
        return $token;
    }
}