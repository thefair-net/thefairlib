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
        $service = Config::get_image('autoCompressService');
        switch($service){
            case 'aliyun':
                $urlAry = parse_url($url);
                $urlAry['host'] = 'image.bj.taooo.cc';
                $urlAry['path'] = $urlAry['path'].'@1pr_'.$width.'w.jpg';
                break;
            default :
                throw new Exception('undefined service type');
        }

        return http_build_url($urlAry);
    }
}
