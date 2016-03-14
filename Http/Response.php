<?php
/**
 * Response.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */

namespace TheFairLib\Http;

abstract class Response
{
    private $_httpCode;
    private $_body;
    private $_headers;
    private $_statusReason;
    private $_protocolVersion;
    private $_cookies;
    private $_sendBody = false;

    public function __construct($body = '', $httpCode = 200){
        $this->_body        = $body;
        $this->_httpCode    = $httpCode;
    }

    public function setCookie(Cookie $cookie){
        $name = $cookie->getName();
        if (empty($name)) return false;
        $name                 = $this->getNewOrExistingKeyInArray($name, $this->_cookies);
        $this->_cookies[$name] = $cookie;
    }

    public function getCookie($name){
        $name = $this->getNewOrExistingKeyInArray($name, $this->_cookies);
        return isset($this->_cookies[$name]) ? $this->_cookies[$name] : NULL;
    }

    public function getCookies(){
        return $this->_cookies;
    }

    public function getHttpCode(){
        return $this->_httpCode;
    }

    /**
     * @param $httpCode
     * @return int
     */
    public function setHttpCode($httpCode){
        return $this->_httpCode = (int)$httpCode;
    }

    /**
     * @param $header
     * @param $value
     * @return bool
     */
    public function setHeader($header, $value){
        if (empty($header)) return false;
        $header                 = $this->getNewOrExistingKeyInArray($header, $this->_headers);
        $this->_headers[$header] = $value;
        if (strtolower($header) === 'location'
            && ($this->_httpCode < 300 || $this->_httpCode > 399)
        ) {
            $this->setHttpCode(301);
        }
    }

    public function getHeader($header){
        $header = $this->getNewOrExistingKeyInArray($header, $this->_headers);
        return isset($this->_headers[$header]) ? $this->_headers[$header] : NULL;
    }

    /**
     * @return string
     */
    public function getBody(){
        return $this->_body;
    }

    /**
     * @param $body
     */
    public function setBody($body){
        $this->_body = $body;
    }

    public function getHeaders(){
        return $this->_headers;
    }

    public function setHeaders(array $headers){
        $this->_headers = $headers;
    }

    public function send($dealHeader = true)
    {
        $body    = $this->_getBodyToSend();
        if($dealHeader === true){
            header_remove();
            $headers = $this->_getHeadersToSend($body);
            foreach ($headers as $header) {
                header($header);
            }
            $cookies = $this->getCookies();
            if (!empty($cookies)) {
                foreach ($cookies as $cookie) {
                    setcookie($cookie->getName(), $cookie->getValue(),
                        $cookie->getExpire(), $cookie->getPath(),
                        $cookie->getDomain(), $cookie->getSecure(),
                        $cookie->getHttpOnly());
                }
            }
        }

        if ($this->_sendBody) {
            print $this->_sendBody;
            exit;
        }else{
            return $body;
        }
    }

    private function _getHeadersToSend(&$body)
    {
        $headers = array();
        if (isset($body)) {
            $this->setHeader('Content-Length', strlen($body));
        }
        $headers[] = 'HTTP/'.$this->getProtocolVersion().' '.$this->getHttpCode().' '.$this->getStatusReason();
        if (($h = $this->getHeaders())) {
            foreach ($h as $header_key => $header_value) {
                $headers[] = $header_key.': '.$header_value;
            }
        }
        return $headers;
    }
    protected function _getBodyToSend(){
        $this->setHeader('Content-Type', $this->_getContentType());
        return $this->_sendBody = $this->_serialize($this->_body);
    }


    public function setSerializer($Serializer)
    {
        $this->Serializer = $Serializer;
    }
    public function isStatusError()
    {
        //http://en.wikipedia.org/wiki/List_of_HTTP_status_codes
        return ($this->status_code > 399);
    }

    private function getNewOrExistingKeyInArray($key, $array)
    {
        if (empty($array)) return $key;
        $keys    = array_keys($array);
        $low_key = strtolower($key);
        foreach ($keys as $existing_key) {
            if ($low_key === strtolower($existing_key)) {
                return $existing_key;
            }
        }
        return $key;
    }
    public function getStatusReason()
    {
        if (empty($this->_statusReason)) {
            if (empty($this->_httpCode)) {
                $this->_httpCode = 200;
                return 'OK (default)';
            }
            $reasons = array(
                '100' => 'Continue',
                '101' => 'Switching Protocols',
                '102' => 'Processing',
                '200' => 'OK',
                '201' => 'Created',
                '202' => 'Accepted',
                '203' => 'Non-Authoritative Information',
                '204' => 'No Content',
                '205' => 'Reset Content',
                '206' => 'Partial Content',
                '207' => 'Multi-Status',
                '208' => 'Already Reported',
                '226' => 'IM Used',
                '300' => 'Multiple Choices',
                '301' => 'Moved Permanently',
                '302' => 'Found',
                '303' => 'See Other',
                '304' => 'Not Modified',
                '305' => 'Use Proxy',
                '306' => 'Switch Proxy',
                '307' => 'Temporary Redirect',
                '308' => 'Permanent Redirect',
                '400' => 'Bad Request',
                '401' => 'Unauthorized',
                '402' => 'Payment Required',
                '403' => 'Forbidden',
                '404' => 'Not Found',
                '405' => 'Method Not Allowed',
                '406' => 'Not Acceptable',
                '407' => 'Proxy Authentication Required',
                '408' => 'Request Timeout',
                '409' => 'Conflict',
                '410' => 'Gone',
                '411' => 'Length Required',
                '412' => 'Precondition Failed',
                '413' => 'Request Entity Too Large',
                '414' => 'Request-URI Too Long',
                '415' => 'Unsupported Media Type',
                '416' => 'Requested Range Not Satisfiable',
                '417' => 'Expectation Failed',
                '418' => 'I\'m a teapot',
                '420' => 'Enhance Your Calm',
                '422' => 'Unprocessable Entity',
                '423' => 'Locked',
                '424' => 'Failed Dependency',
                '425' => 'Unordered Collection',
                '426' => 'Upgrade Required',
                '428' => 'Precondition Required',
                '429' => 'Too Many Requests',
                '431' => 'Request Header Fields Too Large',
                '444' => 'No Response',
                '449' => 'Retry With',
                '450' => 'Blocked by Windows Parental Controls',
                '499' => 'Client Closed Request',
                '500' => 'Internal Server Error',
                '501' => 'Not Implemented',
                '502' => 'Bad Gateway',
                '503' => 'Service Unavailable',
                '504' => 'Gateway Timeout',
                '505' => 'HTTP Version Not Supported',
                '506' => 'Variant Also Negotiates',
                '507' => 'Insufficient Storage',
                '508' => 'Loop Detected',
                '509' => 'Bandwidth Limit Exceeded',
                '510' => 'Not Extended',
                '511' => 'Network Authentication Required',
                '598' => 'Network read timeout error',
                '599' => 'Network connect timeout error');
            if (isset($reasons[$this->_httpCode])) {
                $this->_statusReason = $reasons[$this->_httpCode];
            }
        }
        return $this->_statusReason;
    }

    public function setStatusReason($statusReason){
        $this->_statusReason = $statusReason;
    }

    public function removeHeader($header){
        if (empty($header)) return false;
        $header = $this->getNewOrExistingKeyInArray($header, $this->_headers);
        unset($this->_headers[$header]);
        return true;
    }

    public function getProtocolVersion(){
        if (empty($this->_protocolVersion)) {
            if (isset($_SERVER['SERVER_PROTOCOL'])) {
                list(, $this->_protocolVersion) = explode('/', $_SERVER['SERVER_PROTOCOL']);
            }
            else {
                $this->_protocolVersion = '1.0';
            }
        }
        return $this->_protocolVersion;
    }

    public function setProtocolVersion($protocolVersion){
        $this->_protocolVersion = $protocolVersion;
    }

    public function getSendBody(){
        return $this->_sendBody;
    }

    public function setSendBody($sendBody){
        $this->_sendBody = $sendBody;
    }

    abstract protected function _serialize($content);

    abstract protected function _getContentType();

    public function isInvalidHttpCode(){
        return $this->_httpCode < 100 || $this->_httpCode >= 600;
    }
}