<?php
/**
 * Created by xiangc
 * Date: 2018/6/15
 * Time: 15:11
 */

namespace TheFairLib\Utility;

use TheFairLib\Utility\ParseTools\Parser;
use TheFairLib\Utility\ParseTools\ReverseParse;


class RamlParser
{
    private static $instance;

    /**
     * @return RamlParser
     */
    public static function Instance(){
        $class = get_called_class();
        if(empty(self::$instance[$class])){
            self::$instance[$class] = new $class();
        }
        return self::$instance[$class];
    }

    /**
     * 将html转为raml
     * @param $content
     * @throws \PHPHtmlParser\Exceptions\UnknownChildTypeException
     * @return string
     */
    public function parseHtmlToRaml($content){
        return Parser::Instance()->parseToRAML($content);
    }


    /**
     * 将raml转为html
     * @param $content
     * @return string
     */
    public function parseRamlToHtml($content){
        return ReverseParse::Instance()->parseRAMLToHtml($content);
    }

}