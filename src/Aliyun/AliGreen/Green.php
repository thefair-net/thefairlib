<?php
/**
 * Green.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */

namespace TheFairLib\Aliyun\AliGreen;

use Green\Request\V20170112\ImageSyncScanRequest;
use Green\Request\V20170112\TextScanRequest;
use TheFairLib\Aliyun\Exception;
use TheFairLib\Utility\Utility;

include_once 'aliyuncs/aliyun-php-sdk-core/Config.php';
date_default_timezone_set("PRC");

class Green
{
    protected $_config = [];

    public function __construct($config)
    {
        if (empty($config['access_key_id']) || empty($config['access_key_secret'])) {
            throw new Exception('config error');
        }
        $this->_config = $config;
    }

    /**
     * 文案检测
     *
     * @param $content
     * @param array $scenes
     * @param array $labelWhiteList
     * @param array $passSuggestionList
     * @param array $blockResult
     * @return bool
     * @throws Exception
     */
    public function singleTextScan($content, $scenes = ["antispam"], $labelWhiteList = [], $passSuggestionList = ['pass'], &$blockResult = [])
    {

        $request = new TextScanRequest();
        $params = [
            "tasks" => [[
                'dataId' => uniqid(),
                'content' => $content,
            ]],
            "scenes" => $scenes];

        $taskResults = $this->sendRequest($request, $params);
        $taskResult = current($taskResults);
        if(200 == $taskResult['code']){
            $sceneResults = $taskResult['results'];
            foreach ($sceneResults as $sceneResult) {
                if(!empty($labelWhiteList) && in_array($sceneResult['label'], $labelWhiteList)){
                    continue;
                }
                $suggestion = $sceneResult['suggestion'];
                if(!in_array($suggestion , $passSuggestionList)){
                    $blockResult[] = $sceneResult;
                }
            }
        }else{
            throw new Exception(Utility::encode($taskResults));
        }

        return !empty($blockResult) ? false : true;

    }

    /**
     * 图片扫描
     *
     * @param $imgUrl
     * @param array $scenes
     * @param array $labelWhiteList
     * @param array $passSuggestionList
     * @param array $blockResult
     * @param array $orcResult
     * @return bool
     * @throws Exception
     */
    public function singleImageScan($imgUrl, $scenes = ["porn"], $labelWhiteList = [], $passSuggestionList = ['pass'], &$blockResult = [], &$orcResult = [])
    {

        $request = new ImageSyncScanRequest();
        $params = [
            "tasks" => [[
                'dataId' => uniqid(),
                'url' => $imgUrl,
                'time' => round(microtime(true)*1000),
            ]],
            "scenes" => $scenes];

        $taskResults = $this->sendRequest($request, $params);
        $taskResult = current($taskResults);
        if(200 == $taskResult['code']){
            $sceneResults = $taskResult['results'];
            foreach ($sceneResults as $sceneResult) {
                if(!empty($labelWhiteList) && in_array($sceneResult['label'], $labelWhiteList)){
                    continue;
                }
                $suggestion = $sceneResult['suggestion'];
                if($sceneResult['scene'] != 'ocr' && !in_array($suggestion , $passSuggestionList)){
                    $blockResult[] = $sceneResult;
                }

                if($sceneResult['scene'] == 'ocr'){
                    $orcResult[] = $sceneResult;
                }

            }
        }else{
            throw new Exception(Utility::encode($taskResults));
        }

        return !empty($blockResult) ? false : true;

    }

    public function sendRequest(\RoaAcsRequest $request, $params)
    {
        $iClientProfile = \DefaultProfile::getProfile("cn-hangzhou", $this->_config["access_key_id"], $this->_config["access_key_secret"]); // TODO
        \DefaultProfile::addEndpoint("cn-hangzhou", "cn-hangzhou", "Green", "green.cn-hangzhou.aliyuncs.com");
        $client = new \DefaultAcsClient($iClientProfile);
        $request->setMethod("POST");
        $request->setAcceptFormat("JSON");

        $request->setContent(json_encode($params));

        try {
            $response = $client->getAcsResponse($request);
            $response = Utility::decode(Utility::encode($response));
            if (200 == $response['code']) {
                return $response['data'];
            } else {
                throw new Exception(Utility::encode($response));
            }
        } catch (Exception $e) {
            throw new Exception($e);
        }

    }
}