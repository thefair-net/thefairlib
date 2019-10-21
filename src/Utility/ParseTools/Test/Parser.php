<?php
/**
 * Created by xiangc
 * Date: 2018/6/15
 * Time: 15:18
 */

namespace TheFairLib\Utility\ParseTools\Test;


class Parser
{
    public static function parseHtmlToRaml(){
        $content = file_get_contents(__DIR__ . '/demo3.html');
        $ret = \TheFairLib\Utility\ParseTools\Parser::Instance()->parseToRAML($content);
        return $ret;
    }

    public static function parseRamlToHtml(){
        $content = file_get_contents(__DIR__ . '/ffchild.json');
        $ret = \TheFairLib\Utility\ParseTools\ReverseParse::Instance()->parseRAMLToHtml($content);
        return $ret;
    }
}