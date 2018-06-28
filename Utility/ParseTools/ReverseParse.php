<?php

namespace TheFairLib\Utility\ParseTools;

/**
 * Created by xiangc
 * Date: 2018/6/14
 * Time: 13:58
 */


class ReverseParse{

    private static $instance;

    /**
     * @return ReverseParse
     */
    static public function Instance()
    {
        $class = get_called_class();
        if (empty(self::$instance[$class])) {
            self::$instance[$class] = new $class();
        }
        return self::$instance[$class];
    }


    public function parseRAMLToHtml($content)
    {
        $fileObject = json_decode($content);
        $html = $this->_parseBody($fileObject);
//        print $html;
        return $html;
    }

    private function _parseBody($sourceRAML)
    {
        $result = '<header><meta http-equiv="Content-Type" content="text/html;charset=utf-8"></header>';
        foreach ($sourceRAML as $pLevelItem) {
            $oneSegment = "";
            if ($pLevelItem->type == 0) {
                if (!empty($pLevelItem->li)) {
                    $oneSegment = $this->_buildLi($pLevelItem);
                } else {
                    if (!empty($pLevelItem->text->markups)) {
                        $buildResult = $this->_buildMarkupElements($pLevelItem, $pLevelItem->text->text);
                    } else {
                        $buildResult = $this->_buildTag($pLevelItem->text->text, 'p', '');
                    }
                    $attrs = "";
                    if (!empty($pLevelItem->text->align)) {
                        $attrs = "style='text-align:{$pLevelItem->text->align}'";
                    }
                    if(!empty($pLevelItem->id)){
                        $attrs .= " dataid='{$pLevelItem->id}'";
                    }
                    $oneSegment = $this->_buildTag($buildResult, 'p', $attrs);
                }

            } else if ($pLevelItem->type == 1) {
                $oneSegment = $this->_buildImg($pLevelItem);
            }
            $result .= ($oneSegment . "\n");
        }

        return $result;
    }

    /**
     * 给内容打标记
     * @param $item
     * @param $text
     * @return string
     */
    private function _buildMarkupElements($item, $text)
    {
        if (empty($item->text->markups)) {
            return '';
        }
        $markups = $item->text->markups;
        usort($markups, function ($one, $two) {
            if ($one->start == $two->start) {
                return $two->end - $one->end;
            }
            return $two->start - $one->start;
        });

        $currentRet = $text;
        for ($index = 0; $index < sizeof($markups); $index++) {
            $markupItem = $markups[$index];

            if ($markupItem->tag == 'strong') {
                $currentRet = $this->_insertTag($markupItem, $currentRet, 'strong', '') . "\n";
            } else if ($markupItem->tag == 'color') {
                $currentRet = $this->_insertTag($markupItem, $currentRet, 'span', 'style="color:red"') . "\n";
            }

        }
        return $currentRet;
    }

    private function _buildTag($text, $tagName, $attrs)
    {
        return "<$tagName $attrs>" . $text . "</$tagName>";
    }

    /**
     * 插入一个标签
     * @param $markup
     * @param $text
     * @param $tagName
     * @param $attrs
     * @return string
     */
    private function _insertTag($markup, $text, $tagName, $attrs)
    {
        $actStart = ParseUtils::Instance()->s_strpos($text, $markup->text, $markup->start);
        $actEnd = $actStart + ParseUtils::Instance()->s_strlen($markup->text);
        $pres = ParseUtils::Instance()->s_subStr($text, 0, $actStart);
        $ends = ParseUtils::Instance()->s_subStr($text, $actEnd);

        return $pres . "<$tagName $attrs>" . $markup->text . "</$tagName>" . $ends;
    }

    /**
     * 生成一个html-li节点
     * @param $liItem
     * @return string
     */
    private function _buildLi($liItem)
    {
        if(!empty($liItem->id)){
            $attrs = " dataid='{$liItem->id}'";
        }
        return "<ul>" .
            "<li {$attrs}>" . $liItem->text->text . "</li>" .
            "</ul>";
    }

    /**
     * 生成一个html-img节点
     * @param $imgItem
     * @return string
     */
    private function _buildImg($imgItem)
    {
        if(!empty($imgItem->id)){
            $attrs = " dataid='{$imgItem->id}'";
        }
        return "<img {$attrs} src='{$imgItem->image->source}'/>";
    }
}
