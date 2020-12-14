<?php

declare(strict_types=1);

/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

use TheFairLib\Constants\InfoCode;
use TheFairLib\Exception\ServiceException;
use Hyperf\Contract\ConfigInterface;
use Hyperf\DbConnection\Db;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\Snowflake\IdGenerator\SnowflakeIdGenerator;
use Hyperf\Snowflake\IdGeneratorInterface;
use Hyperf\Snowflake\Meta;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Context;
use Psr\Http\Message\ResponseInterface;
use Swoole\Server as SwooleServer;
use TheFairLib\Library\Utils\AES;

if (!function_exists('encode')) {
    /**
     * 统一封装的encode方法.
     *
     * @param $data
     * @param string $format
     * @return string
     */
    function encode($data, $format = 'json')
    {
        switch ($format) {
            case 'json':
                $ret = json_encode($data, JSON_UNESCAPED_UNICODE);
                break;
            case 'base64':
                $ret = base64_encode($data);
                break;
            case 'serialize':
                $ret = serialize($data);
                break;
            default:
                $ret = $data;
        }

        return $ret;
    }
}

if (!function_exists('decode')) {
    /**
     * 统一封装的decode方法.
     *
     * @param $data
     * @param string $format
     * @return mixed|string
     */
    function decode($data, $format = 'json')
    {
        switch ($format) {
            case 'json':
                //fix bigint转为科学记数法
                $ret = json_decode($data, true, 512, JSON_BIGINT_AS_STRING);
                break;
            case 'base64':
                $ret = base64_decode($data);
                break;
            case 'serialize':
                $ret = unserialize($data);
                break;
            default:
                $ret = $data;
        }

        return $ret;
    }
}

if (!function_exists('generateSnowId')) {
    /**
     * 分布式全局唯一ID生成算法
     * @return int
     */
    function generateSnowId()
    {
        $container = ApplicationContext::getContainer();
        /**
         * @var SnowflakeIdGenerator $generator
         */
        $generator = $container->get(IdGeneratorInterface::class);
        return $generator->generate();
    }
}

if (!function_exists('degenerateSnowId')) {
    /**
     * 根据ID反推对应的Meta
     * @param $id
     * @return Meta
     */
    function degenerateSnowId($id)
    {
        $container = ApplicationContext::getContainer();
        /**
         * @var SnowflakeIdGenerator $generator
         */
        $generator = $container->get(IdGeneratorInterface::class);

        return $generator->degenerate($id);
    }
}

if (!function_exists('arrayGet')) {
    /**
     * 以“.”为分隔符获取多维数组的值
     *
     * @param $array
     * @param $key
     * @param null $default
     * @return mixed
     */
    function arrayGet($array, $key, $default = null)
    {
        if (is_null($key)) {
            return $array;
        }

        if (isset($array[$key])) {
            return $array[$key];
        }

        foreach (explode('.', $key) as $segment) {
            if (!is_array($array) || !array_key_exists($segment, $array)) {
                return value($default);
            }

            $array = $array[$segment];
        }

        return $array;
    }
}

if (!function_exists('getUuid')) {
    /**
     * 获取数据库uuid.
     *
     * @return int uuid
     */
    function getUuid()
    {
        $ret = Db::select('select uuid_short() as uuid');
        $uuid = $ret[0] ?? null;
        if (!empty($uuid->uuid)) {
            return intval(substr("{$uuid->uuid}", -19));
        }
        throw new ServiceException('uuid error', [], InfoCode::SERVER_CODE_ERROR);
    }
}

if (!function_exists('hideStr')) {

    /**
     * 隐藏字符串
     *
     * @param $str
     * @param string $symbol
     * @param int $count
     * @return string
     */
    function hideStr($str, int $count = 0, string $symbol = '*'): string
    {
        $str = strval($str);
        $len = mb_strlen($str, 'UTF-8');
        if ($len < 8) {
            return $str;
        }
        return mb_substr($str, 0, 3) . str_repeat($symbol, $count ?: $len - 6) . mb_substr($str, -3);
    }
}

if (!function_exists('rd_debug')) {

    /**
     * 本地调试
     *
     * @param $data
     */
    function rd_debug($data)
    {
        if (env('PHASE', 'rd') == 'rd') {
            print_r(['data' => $data]);
        }
    }
}

if (!function_exists('unCamelize')) {

    /**
     * 驼峰命名转下划线命名
     *
     * @param $words
     * @return string
     */
    function unCamelize($words)
    {
        return strtolower(preg_replace('~(?<=\\w)([A-Z])~', '_$1', $words));
    }
}

if (!function_exists('camelize')) {

    /**
     * 下划线命名转小驼峰命名
     *
     * @param $words
     * @return string
     */
    function camelize($words)
    {
        return lcfirst(str_replace([' ', '_', '-'], '', ucwords($words, ' _-')));
    }
}

if (!function_exists('bigCamelize')) {

    /**
     * 下划线命名转大驼峰命名
     *
     * @param $words
     * @return string
     */
    function bigCamelize($words)
    {
        $separator = '/';
        return preg_replace_callback("~(?<={$separator})([a-z])~", function ($matches) {
            return strtoupper($matches[0]);
        }, $separator . ltrim($words, $separator));
    }
}

if (!function_exists('getServerLocalIp')) {
    /**
     * 获取服务端内网ip地址
     *
     * @return string
     */
    function getServerLocalIp(): string
    {
        $ip = '127.0.0.1';
        $ips = array_values(swoole_get_local_ip());
        foreach ($ips as $v) {
            if ($v && $v != $ip) {
                $ip = $v;
                break;
            }
        }

        return $ip;
    }
}

if (!function_exists('input')) {
    /**
     * 参数请求
     *
     * @param $name
     * @param $default
     * @return mixed
     */
    function input(string $name, $default = '')
    {
        $id = RequestInterface::class . ':params:' . $name;
        if (Context::has($id)) {
            return Context::get($id, $default);
        }

        /**
         * @var RequestInterface $request
         */
        $request = ApplicationContext::getContainer()->get(RequestInterface::class);
        return $request->input($name, $default);
    }
}


if (!function_exists('inputs')) {
    /**
     * 所有请求参数
     *
     * @return array
     */
    function inputs(): array
    {
        /**
         * @var RequestInterface $request
         */
        $request = ApplicationContext::getContainer()->get(RequestInterface::class);
        return $request->all();
    }
}

if (!function_exists('getConfig')) {
    /**
     * 配置文件获取
     *
     * @param string $key
     * @param null $default
     * @return mixed
     */
    function getConfig(string $key, $default = null)
    {
        return ApplicationContext::getContainer()->get(ConfigInterface::class)->get($key, $default);
    }
}

if (!function_exists('now')) {
    /**
     * 获得时间
     *
     * @param int $time
     * @return false|string
     */
    function now(int $time = 0)
    {
        return date('Y-m-d H:i:s', $time > 0 ? $time : time());
    }
}


if (!function_exists('getRpcLogArguments')) {
    /**
     * 获取要存储的日志部分字段，monolog以外的业务信息
     *
     * @return array
     */
    function getRpcLogArguments()
    {
        /**
         * @var RequestInterface $request
         */
        $request = ApplicationContext::getContainer()->get(RequestInterface::class);

        $params = $request->all();
        unset($params['__auth']);
        $clientInfo = getClientInfo();
        $len = strlen(encode($params));
        return [
            'server_ip' => getServerLocalIp(),
            'client_ip' => arrayGet($clientInfo, 'remote_ip'),
            'server_time' => now(),
            'pid' => posix_getpid(),//得到当前 Worker 进程的操作系统进程 ID
            'uri' => $request->getUri()->getPath(),
            'params' => $len <= 2048 ? $params : ['len' => $len, 'msg' => '...'],
            'method' => $request->getMethod(),
            'execution_time' => round((microtime(true) - Context::get('execution_start_time')) * 1000, 2),
            'request_body_size' => $len,
            'response_body_size' => Context::get('server:response_body_size'),
        ];
    }
}


if (!function_exists('getHttpLogArguments')) {
    /**
     * 获取要存储的日志部分字段，monolog以外的业务信息
     *
     * @return array
     */
    function getHttpLogArguments()
    {
        /**
         * @var RequestInterface $request
         */
        $request = ApplicationContext::getContainer()->get(RequestInterface::class);

        $params = $request->all();
        unset($params['__auth']);
        $sessionId = $request->cookie('PHPSESSID');
        $len = strlen(encode($params));
        $uri = $request->getUri()->getPath();
        if (in_array($uri, ['/favicon.ico'])) {
            return [];
        }
        return [
            'server_ip' => getServerLocalIp(),
            'client_ip' => $request->getServerParams(),
            'server_time' => now(),
            'pid' => posix_getpid(),//得到当前 Worker 进程的操作系统进程 ID
            'session_id' => $sessionId,
            'x_thefair_ua' => $request->getHeader('x-thefair-ua'),
            'user_agent' => $request->getHeader('user-agent'),
            'cid' => $request->getHeader('x-thefair-cid'),
            'uri' => $uri,
            'url' => $request->fullUrl(),
            'params' => $len <= 2048 ? $params : ['len' => $len, 'msg' => '...'],
            'method' => $request->getMethod(),
            'execution_time' => round((microtime(true) - Context::get('execution_start_time')) * 1000, 2),
            'request_body_size' => $len,
            'response_body_size' => Context::get('server:response_body_size'),
        ];
    }
}

if (!function_exists('getRpcClientIp')) {
    /**
     * 获得 Rpc client ip
     *
     * @return string
     */
    function getRpcClientIp(): string
    {
        return getClientInfo()['remote_ip'];
    }
}

if (!function_exists('getClientInfo')) {
    /**
     * 获得 Rpc client ip
     *
     * @return array
     */
    function getClientInfo(): array
    {
        /**
         * @var Hyperf\HttpMessage\Server\Response $response
         */
        $response = Context::get(ResponseInterface::class);

        /**
         * @var SwooleServer $server
         */
        $server = $response->getAttribute('server');
        $fd = $response->getAttribute('fd');
        $clientInfo = $server->getClientInfo($fd);
        if ($connectTime = arrayGet($clientInfo, 'connect_time')) {
            $clientInfo['connect_time'] = date('Y-m-d H:i:s', $connectTime);
        }
        return [
            'remote_port' => arrayGet($clientInfo, 'remote_port'),
            'remote_ip' => arrayGet($clientInfo, 'remote_ip'),
            'connect_time' => arrayGet($clientInfo, 'connect_time'),
        ];
    }
}


if (!function_exists('stringToInt')) {
    /**
     * string 转 int，不保证唯一性
     *
     * @param string $stringToInt
     * @return int
     */
    function stringToInt(string $stringToInt): int
    {
        return intval(crc32(md5((string)$stringToInt)));
    }
}

if (!function_exists('getStaging')) {
    function getStaging(): bool
    {
        return env('PHASE') != "prod";
    }
}

if (!function_exists('getPrefix')) {
    /**
     * redis 前缀
     *
     * @param $type
     * @param $dataType
     * @return string
     */
    function getPrefix($type, $dataType)
    {
        $productPrefix = '';
        if (env('PRODUCT_NAME')) {
            $productPrefix = env('PRODUCT_NAME') . '#';
        }
        if (!in_array($type, ['Cache', 'Storage']) || !in_array($dataType, ['key', 'hash', 'set', 'zset', 'list', 'string', 'geo'])) {
            throw new ServiceException('Redis cache prefix config error!');
        }
        return $productPrefix . $type . '#' . env('PHASE', 'prod') . '#' . $dataType . '#';
    }
}

if (!function_exists('getItemListByPageFromCache')) {
    function getItemListByPageFromCache(string $pool, $listCacheKey, $lastItemId, $order = 'desc', $itemPerPage = 20, $withScores = false): array
    {
        $total = \TheFairLib\Library\Cache\Redis::getContainer($pool)->zCard($listCacheKey);
        $itemPerPage = min(50, $itemPerPage);
        $pageCount = ceil($total / $itemPerPage);
        $currentPage = 1;
        $list = [];
        if ($total) {
            if (!empty($lastItemId)) {
                $start = getItemRankFromCache($pool, $listCacheKey, $lastItemId, $order);
                $start += 1;
            } else {
                $start = (int)$lastItemId;
            }

            $end = $start + $itemPerPage - 1;
            $currentPage = ceil($end / $itemPerPage);
            $funcName = $order == 'desc' ? 'zRevRange' : 'zRange';

            if ($withScores === true) {
                $list = \TheFairLib\Library\Cache\Redis::getContainer($pool)->$funcName($listCacheKey, $start, $end, true);
            } else {
                $list = \TheFairLib\Library\Cache\Redis::getContainer($pool)->$funcName($listCacheKey, $start, $end);
            }
            if (!empty($list)) {
                $lastItemId = end($list);
                if ($withScores === true) {
                    $lastItemId = key($list);
                }
            }
        }

        $result = [
            'item_list' => $list,
            'item_count' => $total,
            'item_per_page' => $itemPerPage,
            'page_count' => $pageCount,
            'current_page' => $currentPage,
        ];

        $lastPos = getItemRankFromCache($pool, $listCacheKey, $lastItemId, $order);
        if ($lastPos != $total - 1 && !empty($list)) {
            $result['last_item_id'] = $lastItemId;
        }
        return $result;
    }
}

if (!function_exists('getItemRankFromCache')) {
    /**
     * 获取缓存中成员的排名,用于展示未读消息数或者获取列表的起始位置
     *
     * @param string $pool
     * @param $listCacheKey
     * @param $lastItemId
     * @param string $order
     * @return int
     */
    function getItemRankFromCache(string $pool, $listCacheKey, $lastItemId, $order = 'desc'): int
    {
        return $order == 'desc' ? (int)\TheFairLib\Library\Cache\Redis::getContainer($pool)->zRevRank($listCacheKey, $lastItemId) :
            (int)\TheFairLib\Library\Cache\Redis::getContainer($pool)->zRank($listCacheKey, $lastItemId);
    }
}

if (!function_exists('encrypt')) {
    /**
     * 加密 说明文档 https://qydev.weixin.qq.com/wiki/index.php?title=%E5%8A%A0%E8%A7%A3%E5%AF%86%E6%96%B9%E6%A1%88%E7%9A%84%E8%AF%A6%E7%BB%86%E8%AF%B4%E6%98%8E
     *
     *
     * @param $data
     * @param $aesKey https://www.php.net/manual/zh/function.openssl-decrypt.php
     * @return string
     */
    function encrypt(string $data, string $aesKey)
    {
        $aesKey = base64_decode($aesKey . '=', true);
        return base64_encode(AES::encrypt(
            $data,
            $aesKey,
            substr($aesKey, 0, 16)
        ));
    }
}

if (!function_exists('decrypt')) {
    /**
     * 解密 说明文档 https://qydev.weixin.qq.com/wiki/index.php?title=%E5%8A%A0%E8%A7%A3%E5%AF%86%E6%96%B9%E6%A1%88%E7%9A%84%E8%AF%A6%E7%BB%86%E8%AF%B4%E6%98%8E
     * https://developers.weixin.qq.com/doc/offiaccount/Message_Management/Message_encryption_and_decryption_instructions.html
     *
     *
     * @param $data
     * @param $aesKey https://www.php.net/manual/zh/function.openssl-decrypt.php
     * @return string
     */
    function decrypt(string $data, string $aesKey)
    {
        $aesKey = base64_decode($aesKey . '=', true);
        return AES::decrypt(
            base64_decode($data, true),
            $aesKey,
            substr($aesKey, 0, 16)
        );
    }
}


if (!function_exists('utf8Len')) {

    /**
     * 字符串长度
     *
     * @param string $content
     * @return int
     */
    function utf8Len(string $content): int
    {
        return (int)mb_strlen($content, "UTF-8");
    }
}

if (!function_exists('stringGroup')) {

    /**
     * 字符串分组
     *
     * @param string $content
     * @param int $contentLenLimit
     * @return array
     */
    function stringGroup(string $content, int $contentLenLimit = 1000): array
    {
        $len = utf8Len($content);
        if ($len <= 0) {
            return [];
        }
        if ($len <= $contentLenLimit) {
            return [
                [
                    'content' => $content,
                    'order' => 1,
                ],
            ];
        }
        $count = ceil($len / $contentLenLimit);
        $data = [];
        for ($i = 1; $i <= $count; $i++) {
            $subContent = mb_substr($content, ($i - 1) * $contentLenLimit, $contentLenLimit, 'utf-8');
            $data[] = [
                'order' => $i,
                'content' => $subContent,
            ];
        }
        return $data;
    }
}

if (!function_exists('esFormatDate')) {

    /**
     * 格式化时间
     * @param $date
     * @return string
     */
    function esFormatDate($date): string
    {
        if (empty($date)) {
            $date = 0;
        }
        $date = is_int($date) ? date('Y-m-d H:i:s', $date) : $date;

        $result = preg_match('/[1-9]\d+\-\d+\-\d+( \d+:\d+:\d+)?/', $date, $matches);
        if ($result) {
            $date = $matches[0];
            if (strlen($date) < 13) {
                $date = $date . " 00:00:01";
            }

            $date = str_replace(' ', 'T', $date) . 'Z';

            $date = str_replace('Z', "+08:00", $date);

            return $date;
        } else {
            return '1970-01-01T00:00:01Z';
        }
    }
}