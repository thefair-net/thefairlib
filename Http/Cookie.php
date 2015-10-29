<?php
/**
 * Cookie.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Http;

class Cookie
{
    private $_name;
    private $_value;
    private $_expire = 0;
    private $_path;
    private $_domain;
    private $_secure = false;
    private $_httpOnly = true;

    public function __construct($name, $value = '', $expire = 0, $path = '/', $domain = '', $secure = false, $httpOnly = true){
        $this->_name        = $name;
        $this->_value       = $value;
        $this->_expire      = $expire;
        $this->_path        = $path;
        $this->_domain      = $domain;
        $this->_secure      = $secure;
        $this->_httpOnly    = $httpOnly;
    }

    public function setDomain($domain){
        return $this->_domain = $domain;
    }

    public function getDomain(){
        return $this->_domain;
    }

    public function setExpire($expire = 0){
        return $this->_expire = $expire;
    }

    public function getExpire(){
        return $this->_expire;
    }

    public function setHttpOnly($httpOnly = true){
        return $this->_httpOnly = $httpOnly;
    }

    public function getHttpOnly(){
        return $this->_httpOnly;
    }

    public function setName($name){
        return $this->_name = $name;
    }

    public function getName(){
        return $this->_name;
    }

    public function setPath($path){
        return $this->_path = $path;
    }

    public function getPath(){
        return $this->_path;
    }

    public function setSecure($secure = false){
        return $this->_secure = $secure;
    }

    public function getSecure(){
        return $this->_secure;
    }

    public function setValue($value){
        return $this->_value = $value;
    }

    public function getValue(){
        return $this->_value;
    }

    public function getHeader(){
        return
            rawurlencode($this->_name).'='.rawurlencode($this->_value)
            .(isset($this->_domain) ? '; Domain='.rawurlencode($this->_domain) : '')
            .(isset($this->_path) ? '; Path='.$this->_path : '')
            .(!empty($this->_expire) ? '; Expires='.gmdate('D, d M Y H:i:s \G\M\T', $this->_expire) : '')
            .(!empty($this->_secure) ? '; Secure' : '')
            .(!empty($this->_httpOnly) ? '; HttpOnly' : '');
    }
}