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
    private $_needMarkUpText=false;

    /**
     * @param bool $needMarkUpText 是否需要给markup加text调试
     * @return RamlParser
     */
    static public function Instance($needMarkUpText=false)
    {
        $class = get_called_class();
        $key = $class . strval($needMarkUpText);
        if (empty(self::$instance[$key])) {
            self::$instance[$key] = new $class();
        }
        self::$instance[$key]->_needMarkUpText = $needMarkUpText;
        return self::$instance[$key];
    }

    /**
     * 将html转为raml
     * @param $content
     * @return string
     */
    public function parseHtmlToRaml($content){
        return Parser::Instance($this->_needMarkUpText)->parseToRAML($content);
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