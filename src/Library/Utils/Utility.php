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

use TheFairLib\Constants\ServerCode;
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
                if (extension_loaded('jsond')) {
                    $ret = jsond_encode($data, JSON_UNESCAPED_UNICODE);
                } else {
                    $ret = json_encode($data, JSON_UNESCAPED_UNICODE);
                }
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
                if (extension_loaded('jsond')) {
                    $ret = jsond_decode($data, true);
                } else {
                    $ret = json_decode($data, true);
                }
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
        throw new ServiceException('uuid error', [], ServerCode::SERVER_CODE_ERROR);
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
     * @param null $default
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
        return [
            'server_ip' => getServerLocalIp(),
            'client_ip' => arrayGet($clientInfo, 'remote_ip'),
            'server_time' => now(),
            'pid' => posix_getpid(),//得到当前 Worker 进程的操作系统进程 ID
            'uri' => $request->getUri()->getPath(),
            'params' => $params,
            'method' => $request->getMethod(),
            'execution_time' => round((microtime(true) - Context::get('execution_start_time')) * 1000, 2),
            'request_body_size' => strlen(encode($params)),
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
        return [
            'server_ip' => getServerLocalIp(),
            'client_ip' => $request->getServerParams(),
            'server_time' => now(),
            'pid' => posix_getpid(),//得到当前 Worker 进程的操作系统进程 ID
            'session_id' => $sessionId,
            'x_thefair_ua' => $request->getHeader('x-thefair-ua'),
            'user_agent' => $request->getHeader('user-agent'),
            'cid' => $request->getHeader('x-thefair-cid'),
            'uri' => $request->getUri()->getPath(),
            'url' => $request->fullUrl(),
            'params' => $params,
            'method' => $request->getMethod(),
            'execution_time' => round((microtime(true) - Context::get('execution_start_time')) * 1000, 2),
            'request_body_size' => strlen(encode($params)),
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

