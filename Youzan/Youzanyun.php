<?php
/**
 * Youzan.php
 *
 * @author liumingzhi
 * @version 1.0
 * @copyright 2015-2015
 * @date 16/4/28 下午2:30
 */

namespace TheFairLib\Youzan;

use TheFairLib\Config\Config;
use TheFairLib\DB\Redis\Cache;
use Yaf\Exception;

require_once __DIR__ . '/lib/YZGetTokenClient.php';
require_once __DIR__ . '/lib/YZTokenClient.php';

/**
 * https://www.youzanyun.com/apilist/detail/group_trade/trade/youzan.trades.sold.get
 *
 * Class Youzanyun
 * @package TheFairLib\Youzan
 */
class Youzanyun
{
    static public $instance;

    /**
     * @var \YZTokenClient
     */
    static private $client = null;

    CONST TOKEN = 'CACHE_STRING_YOUZANYUN_ACCESS_TOKEN_INFO';

    static private $config = [];

    /**
     * Youzanyun
     *
     * @param array $config
     * @param string $type
     * @return Youzanyun
     * @throws Exception
     */
    static public function Instance($config = [], $type = 'book')
    {
        $class = get_called_class();
        if (empty(self::$instance)) {
            self::$instance = new $class();
            if (empty($config)) {
                self::$config = Config::get_api_youzan($type);
            } else {
                self::$config = $config;
            }
            if (empty(self::$config)) {
                throw new Exception('error youzanyum appKey');
            }
            self::$client = new \YZTokenClient(self::getToken($type));
        }
        return self::$instance;
    }

    public function get($method, $params = [])
    {
        return self::$client->get($method, self::$config['version'], $params);
    }

    public function post($method, $params = [], $files = [])
    {
        return self::$client->post($method, self::$config['version'], $params, $files);
    }

    static public function getToken($type)
    {
        $redis = Cache::getInstance('default');
        $name = self::TOKEN . '#' . $type;
        $accessToken = $redis->get($name);
        if (empty($accessToken)) {
            $token = new \YZGetTokenClient(self::$config['app_id'], self::$config['secret']);
            $param['kdt_id'] = self::$config['kdt_id'];
            $param['grant_type'] = self::$config['grant_type'];
            $ret = $token->get_token(self::$config['grant_type'], $param);

            if (!empty($ret)) {
                $redis->setex($name, $ret['expires_in'] - 100, $ret['access_token']);
            }
            $accessToken = $ret['access_token'];
        }
        return $accessToken;
    }
}