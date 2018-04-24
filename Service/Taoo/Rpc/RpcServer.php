<?php
/**
 * RpcServer.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */

namespace TheFairLib\Service\Taoo\Rpc;

use TheFairLib\Controller\Service\Error;
use TheFairLib\Exception\Service\ServiceException;
use TheFairLib\Logger\Logger;
use TheFairLib\Service\Swoole\Network\Protocol\BaseServer;
use TheFairLib\Utility\Utility;
use Yaf\Application;
use Yaf\Request\Http;

class RpcServer extends BaseServer
{
    /**
     * @var \Yaf\Application
     */
    protected $_application = false;

    /**
     * 发送数据到客服端
     *
     * @param \swoole_server $server
     * @param $clientId
     * @param $fromId
     * @param $requestData
     * @return mixed
     */
    public function onReceive($server, $clientId, $fromId, $requestData)
    {
        $start = microtime(true);
        $dateTime = date('Y-m-d H:i:s', time());
        $eventType = 'receive';
        $connInfo = $server->connection_info($clientId);
        $clientIp = $connInfo['remote_ip'];
        $serverIp = current(swoole_get_local_ip());
        $url = '';
        $params = [];
        try {
            ob_start();
            $requestData = $this->_decode($requestData);
            $this->_checkAuthorize($requestData['auth']);
            $data = $requestData['request_data'];
            $url = !empty($data['url']) ? $data['url'] : '';
            $_SERVER['REQUEST_URI'] = $url;
            $request = new Http($url);
            if (!empty($data['params'])) {
                $params = $data['params'];
                foreach ($data['params'] as $key => $param) {
                    $request->setParam($key, $param);
                    $_REQUEST[$key] = $_POST[$key] = $_GET[$key] = $param;
                }
            }
            $this->_application->getDispatcher()->catchException(true)->dispatch($request);
            $result = ob_get_contents();
            ob_end_clean();

            $ret = Utility::decode($result);
            $code = 0;
            $msg = '';
            $logType = 'info';
            if (isset($ret['code'])) {
                if ($ret['code'] >= 40000) {
                    $logType = 'error';
                    $msg = $result;
                    $code = $ret['code'];
                }
            }
            $responseTime = microtime(true) - $start;//响应时间

            Logger::Instance()->access($dateTime, $logType, $eventType, $responseTime, $serverIp, $clientIp, $url, $data['params'], $code, $msg);
        } catch (\Exception $e) {
            if ($e instanceof ServiceException) {
                $ret = [
                    'code' => $e->getExtCode(),
                    'message' => $e->getMessage(),
                    'result' => (object)$e->getExtData(),
                ];
            } else {
                $ret = [
                    'code' => 10000,
                    'message' => $e->getMessage(),
                    'result' => (object)[],
                ];
            }

            $result = Utility::encode($ret);

            $msg = "文件名:{$e->getFile()}, 行号:{$e->getLine()}, 错误信息:{$e->getMessage()}, TraceString:{$e->getTraceAsString()}";

            $responseTime = microtime(true) - $start;//响应时间

            Logger::Instance()->access($dateTime, 'error', $eventType, $responseTime, $serverIp, $clientIp, $url, $params, $e->getExtCode(), $msg);
        }
        return $server->send($clientId, $this->_encode($result));
    }

    /**
     * @param \swoole_server $server
     * @param $workerId
     */
    public function onStart($server, $workerId)
    {
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        //检查需要的常量是否存在
        if (!defined('APP_NAME') || !defined('APP_PATH')) {
            Logger::Instance()->error('APP_NAME or APP_PATH is not defined');
            $server->shutdown();
        } else {
            define('APPLICATION_PATH', dirname(__DIR__));
            $this->_application = new Application(APP_PATH . "/config/application.ini");
            ob_start();
            $this->_application->bootstrap()->run();
            ob_end_clean();
        }
        Logger::Instance()->info('onStart');
    }

    /**
     * @param \swoole_server $server
     * @param $workerId
     */
    public function onShutdown($server, $workerId)
    {
        Logger::Instance()->info('onShutdown');
    }

    /**
     * @param \swoole_server $server
     * @param $fd
     * @param $fromId
     */
    public function onConnect($server, $fd, $fromId)
    {
        Logger::Instance()->info('onConnect');
    }

    /**
     * @param \swoole_server $server
     * @param $fd
     * @param $fromId
     */
    public function onClose($server, $fd, $fromId)
    {
        Logger::Instance()->info('onClose');
    }

    /**
     * @param \swoole_server $server
     * @param $taskId
     * @param $fromId
     * @param $data
     */
    public function onTask($server, $taskId, $fromId, $data)
    {
        Logger::Instance()->info('onTask');
    }

    /**
     * @param \swoole_server $server
     * @param $taskId
     * @param $data
     */
    public function onFinish($server, $taskId, $data)
    {
        Logger::Instance()->info('onFinish');
    }

    /**
     * @param \swoole_server $server
     * @param $interval
     */
    public function onTimer($server, $interval)
    {
        Logger::Instance()->info('onTimer');
    }

    /**
     * @param $request
     * @param $response
     */
    public function onRequest($request, $response)
    {
        Logger::Instance()->info('onRequest');
    }

    /**
     * @param $request
     * @param $response
     */
    public function onHttpWorkInit($request, $response)
    {
        Logger::Instance()->info('onHttpWorkInit');
    }

    protected function _encode($data)
    {
        $data = base64_encode(gzcompress($data));
        //因为swoole扩展启用了open_length_check,需要在数据头部增加header @todo 增加长度校验及扩展头
        return pack("N", strlen($data)) . $data;
    }

    protected function _decode($data)
    {
        $data = substr($data, 4);

        return json_decode(gzuncompress(base64_decode($data)), true);
    }

    protected function _checkAuthorize($authData)
    {
        if (empty($authData['app_key']) || empty($authData['app_secret'])) {
            throw new ServiceException('auth config is error');
        }

        if ($authData['app_secret'] != md5(md5($authData['app_key']))) {
            throw new ServiceException('authorize field');
        }
        return true;
    }
}