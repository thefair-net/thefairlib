<?php
namespace TheFairLib\Utility\ParseTools;

/**
 * 对html进行预处理，将html所有tag都加上一个自增属性
 * Created by xiangc
 * Date: 2018/6/13
 * Time: 20:23
 */
require_once __DIR__ . '/simple_html_dom.php';

class PreParser{
    private static $instance;
    private static $counter=0;

    /**
     * @return PreParser
     */
    public static function Instance(){
        $class=get_called_class();
        if(empty(self::$instance[$class])){
            self::$instance[$class] = new $class();
        }
        self::$counter = 0;
        return self::$instance[$class];
    }

    function preHtml($sHtml){
        $html = str_get_html($sHtml);
        $this->addIdToHtml($html);
        return strval($html);
    }

    /**
     * 给HTML加id
     * @param $inItem
     */
    function addIdToHtml(&$inItem){
        foreach($inItem->find('*') as $e){
            if(empty($e->dataId)){
                $e->predataId=self::$counter++;
            }
            $this->addIdToHtml($e);
        }
    }

}