<?php
namespace TheFairLib\Utility\ParseTools;

/**
 * Created by xiangc
 * Date: 2018/6/13
 * Time: 20:28
 */

class NodeBuilder{
    private static $instance;

    /**
     * @return NodeBuilder
     */
    public static function Instance(){
        $class = get_called_class();
        if(empty(self::$instance[$class])){
            self::$instance[$class] = new $class();
        }
        return self::$instance[$class];
    }

    /**
     * 构建文章节点
     * @param $text
     * @param string $lineType
     * @param string $align
     * @return array
     */
    public function buildTextNode($text, $lineType = '', $align = '')
    {
        $ret = [
            'id' => ParseUtils::Instance()->genHash($text),
            'type' => 0,
            'text' => [
                'text' => $text,
            ]
        ];
        if (!empty($lineType)) {
            $ret['text']['linetype'] = $lineType;
        }
        if (!empty($align)) {
            $ret['text']['align'] = $align;
        }

        return $ret;
    }

    /**
     * 构建LI节点
     * @param $text
     * @return array
     */
    public function buildLi($text)
    {
        return [
            "id" => ParseUtils::Instance()->genHash($text),
            "type" => 0,
            "text" => [
                "text" => $text
            ],
            "li" => [
                "type" => "ul",
                "level" => 1,
                "order" => 1
            ],
            "blockquote" => 0,
        ];
    }

    /**
     * 构建Img节点数据
     * @param \PHPHtmlParser\Dom\HtmlNode $item
     * @return array
     */
    public function buildImgNode($item)
    {
        $url = $item->getAttribute('src');
        $width = $item->getAttribute('data-width');
        $height = $item->getAttribute('data-height');
        return [
            "id" => ParseUtils::Instance()->genHash($url),
            "type" => 1,
            "image" => [
                "width" => $width,
                "height" => $height,
                "source" => $url,
            ]
        ];
    }

    /**
     * 构建空节点
     * @return array
     */
    public function buildEmptyNode()
    {
        return [
            'id' => ParseUtils::Instance()->genHash(''),
            'type' => 10,
        ];
    }

    /**
     * 构建一个span markup
     * @param \PHPHtmlParser\Dom\HtmlNode $item
     * @param \PHPHtmlParser\Dom\HtmlNode $baseItem
     * @return array
     * @throws \PHPHtmlParser\Exceptions\UnknownChildTypeException
     */
    function buildSpanMarkup($item, $baseItem)
    {
        $text = ParseUtils::Instance()->trimContent(strip_tags($item->innerHtml()));
        $color = ParseUtils::Instance()->getCssValueFromItem($item, 'color');
        $pos = $this->getStartEnd($item, $baseItem);

        if (!empty($text)) {
            return [
                'tag' => 'color',
                'font-color'=>$color,
                'text' => $text,
                'start' => $pos['start'],
                'end' => $pos['end'],
            ];
        } else {
            return [];
        }
    }


    /**
     * 构建一个普通文本markup节点
     * @param \PHPHtmlParser\Dom\HtmlNode $item
     * @param \PHPHtmlParser\Dom\HtmlNode $baseItem
     * @return array
     * @throws \PHPHtmlParser\Exceptions\UnknownChildTypeException
     */
    function buildStrongMarkup($item, $baseItem)
    {
        $text = ParseUtils::Instance()->trimContent(strip_tags($item->innerHtml()));
        $pos = $this->getStartEnd($item, $baseItem);

        if (!empty($text)) {
            return [
                'tag' => 'strong',
                'text' => $text,
                'start' => $pos['start'],
                'end' => $pos['end'],
            ];
        } else {
            return [];
        }
    }

    function buildSentence($text, $start, $end){
        return [
            'tag' => 'sentence',
            'text' => $text,
            'start' => $start,
            'end' => $end,
        ];
    }


    /**
     * 获取一段文字在另一段文字中的起止位置
     * @param \PHPHtmlParser\Dom\HtmlNode $item
     * @param \PHPHtmlParser\Dom\HtmlNode $baseItem
     * @return array
     */
    function getStartEnd($item, $baseItem)
    {
        $baseOut = $baseItem->outerHtml();
        $itemOut = $item->outerHtml();

        $start = ParseUtils::Instance()->s_strpos($baseOut, $itemOut);
        $subs = ParseUtils::Instance()->s_subStr($baseOut, 0, $start);
        $subs = strip_tags($subs);
        $start = ParseUtils::Instance()->s_strlen(ParseUtils::Instance()->trimContent($subs));

        $end = (int)ParseUtils::Instance()->s_strlen(ParseUtils::Instance()->trimContent(strip_tags($itemOut))) + $start;
        return [
            'start' => $start,
            'end' => $end
        ];
    }
}