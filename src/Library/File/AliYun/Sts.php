<?php
/***************************************************************************
 *
 * Copyright (c) 2020 liumingzhi, Inc. All Rights Reserved
 *
 **************************************************************************
 *
 * @file Sts.php
 * @author liumingzhi(liumingzhij26@gmail.com)
 * @date 2020-11-26 16:27:00
 *
 **/

namespace TheFairLib\Library\File\AliYun;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Hyperf\Cache\Annotation\Cacheable;
use Hyperf\Guzzle\CoroutineHandler;
use TheFairLib\Contract\StsInterface;

class Sts implements StsInterface
{

    /**
     * @var StsConfig
     */
    protected $stsConfig;

    /**
     * @var Client
     */
    public $client;

    public function __construct()
    {
        $this->client = new Client([
            'handler' => HandlerStack::create(new CoroutineHandler()),
            'timeout' => 5,
        ]);
    }

    /**
     * @Cacheable(prefix="ali_sts_v3", value="#{bucket}", ttl=3000)
     *
     * @param string $bucket
     * @return array
     * @throws GuzzleException
     */
    public function sts(string $bucket): array
    {
        $this->stsConfig = make(StsConfig::class);
        $this->stsConfig->getConfigInfo($bucket);

        $param = [
            'Format' => 'JSON',
            'Version' => '2015-04-01',
            'AccessKeyId' => $this->stsConfig->getAccessKeyId(),
            'SignatureMethod' => 'HMAC-SHA1',
            'SignatureVersion' => '1.0',
            'SignatureNonce' => $this->getRandChar(8),
            'Action' => 'AssumeRole',//通过扮演角色接口获取令牌
            'RoleArn' => $this->stsConfig->getRoleArn(),
            'RoleSessionName' => $this->stsConfig->getRoleSessionName(),
            'DurationSeconds' => $this->stsConfig->getDurationSeconds(),
            'Timestamp' => date('Y-m-d', time() - 3600 * 8) . 'T' . date('H:i:s', time() - 3600 * 8) . 'Z'
            //'Policy'=>'' //此参数可以限制生成的 STS token 的权限，若不指定则返回的 token 拥有指定角色的所有权限。
        ];
        $param['Signature'] = $this->computeSignature($param, 'POST');
        $response = $this->client->post($this->stsConfig->getUrl(), [
            'headers' => array_merge([
                'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
            ]),
            'query' => $param,
        ]);
        return $this->render($response->getBody()->getContents());
    }

    private function render($res): array
    {
        $res = decode($res);
        if (empty($res['Credentials'])) {
            return [];
        } else {
            return [
                'accessKeySecret' => $res['Credentials']['AccessKeySecret'],
                'accessKeyId' => $res['Credentials']['AccessKeyId'],
                'expiration' => $res['Credentials']['Expiration'],
                'securityToken' => $res['Credentials']['SecurityToken'],
                'origin' => $this->stsConfig->getOssOrigin(),
            ];
        }
    }

    protected function computeSignature($parameters, $setMethod): string
    {
        ksort($parameters);
        $queryStr = '';
        foreach ($parameters as $key => $value) {
            $queryStr .= '&' . $this->percentEncode($key) . '=' . $this->percentEncode($value);
        }
        $stringToSign = $setMethod . '&%2F&' . $this->percentencode(substr($queryStr, 1));
        return $this->getSignature($stringToSign, $this->stsConfig->getAccessKeySecret() . '&');
    }

    public function getSignature($source, $accessSecret): string
    {
        return base64_encode(hash_hmac('sha1', $source, $accessSecret, true));
    }

    protected function percentEncode($str)
    {
        $res = urlencode($str);
        $res = preg_replace('/\+/', '%20', $res);
        $res = preg_replace('/\*/', '%2A', $res);
        return preg_replace('/%7E/', '~', $res);
    }

    public function getRandChar($length): ?string
    {
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        return $str;
    }

}