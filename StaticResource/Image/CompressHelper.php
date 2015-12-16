<?php
/**
 * Compress.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\StaticResource\Image;

use TheFairLib\Config\Config;
use TheFairLib\StaticResource\Exception;

class CompressHelper
{
    public static function getCompressImgUrl($url, $width){
        $service = Config::get_image('auto_compress_service');
        switch($service){
            case 'aliyun':
                $urlAry = parse_url($url);
                $urlAry['host'] = 'image.bj.taooo.cc';
                $urlAry['path'] = $urlAry['path'].'@1pr_'.$width.'w.jpg';
                break;
            default :
                throw new Exception('undefined service type');
        }

        return $urlAry['scheme'].'://'.$urlAry['host'].$urlAry['path'].(!empty($urlAry['query']) ? '?'.$urlAry['query'] : '');
    }

    /**
     * 自动根据平台，位置自动压缩图片
     *
     * @param $imgTag string 对应conf中的图片位置唯一标志。例如product_single,用于产品信息中single位置
     * @param $options array 参见下方示例
     * @param $platform string iphone/ipad/android/h5
     * @param $resolutionWidth string 屏幕宽度
     * @return string  图片url
     * @throws Exception
     *
     * array(
     * 		'url' => 'http://demo.host/path/{$key1}/{$key2}/{$key3}_demo_'.self::IMG_SELF_WIDTH.'_'.self::IMG_SELF_HEIGHT.'.jpg', //图片地址
     * 		'rules' => array(
     * 			'key1' => 'value1',
     * 			'key2' => 'value2',
     * 			'key3' => 'value3',
     * 		),//图片地址中变量替换的原则
     * 		'custom' => array(
     * 			'640' => 'http://demo.host/path/special/url.jpg',//自定义某个分辨率下的地址形式（用于跟图片宽高无关的地址）
     * 		),
     *      'custom_compress_rate' => 0.5,
     * );
     */
    public static function autoCompressImg($imgTag, $options, $platform, $resolutionWidth){
        if(empty($resolutionWidth)){
            throw new Exception('resolution error');
        }
        $resolutionSetting 	= Config::get_image('resolution_setting');
        if(!empty($resolutionSetting[$platform])){
            $platformSetting = $resolutionSetting[$platform];
        }else{
            $platformSetting = call_user_func_array("array_merge", $resolutionSetting);
        }
        $resolutionWidth = self::getDeviceWithByResolution($platformSetting, $resolutionWidth);

        $allCompressSetting 	= Config::get_image('compress_setting.'.$imgTag);
        if(!empty($allCompressSetting[$platform])){
            $compressSetting = $allCompressSetting[$platform];
        }elseif(!empty($allCompressSetting['default'])){
            $compressSetting = $allCompressSetting['default'];
        }else{
            $compressSetting = call_user_func_array("array_merge", $allCompressSetting);
        }

        //合并临时配置
        if(!empty($options['custom_compress_rate'])){
            $compressRate = $options['custom_compress_rate'];
        }else{
            $compressRate = 1;
        }

        if(!empty($platformSetting) && !empty($compressSetting)){
            $search 	= [];
            $replace	= [];
            if(!empty($options['rules'])){
                foreach($options['rules'] as $k => $v){
                    $search[] 	= '{$'.$k.'}';
                    $replace[]	= $v;
                }
            }

            $tmpSetting = !empty($compressSetting[$resolutionWidth]) ? $compressSetting[$resolutionWidth] : (!empty($compressSetting['default']) ? $compressSetting['default'] : 1);
            $tmpSetting = $tmpSetting * $compressRate;
            if(!empty($tmpSetting)){
                $imgUrl = '';
                if(!empty($options['custom'][$resolutionWidth])){
                    $imgUrl = $options['custom'][$resolutionWidth];
                }elseif(!empty($options['url'])){
                    $imgUrl = $options['url'];
                }
                if(!empty($imgUrl)){
                    $imgUrl = self::getCompressImgUrl($imgUrl, $resolutionWidth * (float) $tmpSetting);
                }
            }else{
                throw new Exception('resolution error 1');
            }
        }else{
            throw new Exception('resolution error 2');
        }


        return $imgUrl;
    }

    public static function getDeviceWithByResolution($resolutionList, $resolutionWidth){
        $min    = false;
        $key    = '';
        foreach($resolutionList as $tmpWidth){
            $tmp = abs($resolutionWidth - $tmpWidth);
            if($min !== false && $tmp <= $min){
                $min = $tmp;
                $key = $tmpWidth;
            }elseif($min === false){
                $min = $tmp;
                $key = $tmpWidth;
            }else{
                continue;
            }
        }
        return $key;
    }
}
