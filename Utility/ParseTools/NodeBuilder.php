<?php

namespace TheFairLib\Utility\ParseTools;

/**
 * Created by xiangc
 * Date: 2018/6/13
 * Time: 20:28
 */

class NodeBuilder
{
    private static $instance;
    private $_needMarkUpText = false;

    /**
     * @param bool $needMarkUpText 是否需要给markup加text调试
     * @return NodeBuilder
     */
    static public function Instance($needMarkUpText = false)
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
     * 构建文章节点
     * @param $text
     * @param string $lineType
     * @param string $align
     * @param string $id
     * @return array
     */
    public function buildTextNode($text, $lineType = '', $align = '', $id)
    {
        if(empty($id)){
            $id = ParseUtils::Instance()->genHash($text . $this->_createGuid());
        }
        $ret = [
            'id' => $id,
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
     * @param $id
     * @return array
     */
    public function buildLi($text, $id)
    {
        if(empty($id)){
            $id = ParseUtils::Instance()->genHash($text . $this->_createGuid());
        }
        return [
            "id" => $id,
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
     * @param $id
     * @return array
     */
    public function buildImgNode($item, $id)
    {

        $url = $item->getAttribute('src');
        $width = $item->getAttribute('data-width');
        $height = $item->getAttribute('data-height');
        if(empty($id)){
            $id = ParseUtils::Instance()->genHash($url . $this->_createGuid());
        }
        $ret = [
            "id" => $id,
            "type" => 1,
            "image" => [
                "source" => $url,
            ]
        ];

        if(!empty($width)){
            $ret['image']['width'] = $width;
        }

        if(!empty($height)){
            $ret['image']['height'] = $height;
        }

        return $ret;
    }

    /**
     * 构建空节点
     * @param $id
     * @return array
     */
    public function buildEmptyNode($id)
    {
        if(empty($id)){
            $id = ParseUtils::Instance()->genHash($this->_createGuid());
        }
        return [
            'id' => $id,
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
            $base = [
                'tag' => 'fontcolor',
                'value' => $color,
                'start' => $pos['start'] . '',
                'end' => $pos['end'] . '',
            ];

            if ($this->_needMarkUpText) {
                $base['text'] = $text;
            }
            return $base;
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
            $base = [
                'tag' => 'strong',
                'start' => $pos['start'] . '',
                'end' => $pos['end'] . '',
            ];
            if ($this->_needMarkUpText) {
                $base['text'] = $text;
            }
            return $base;
        } else {
            return [];
        }
    }

    /**
     * build句子节点
     * @param $text
     * @param $start
     * @param $end
     * @return array
     */
    function buildSentence($text, $start, $end)
    {
        if(!empty($text)){
            $base = [
                'tag' => 'sentence',
                'start' => $start . '',
                'end' => $end . '',
            ];
            if($this->_needMarkUpText){
                $base['text'] = $text;
            }
            return $base;
        } else {
            return [];
        }
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
            'start' => $start . '',
            'end' => $end . '',
        ];
    }



    private function _createGuid() {
        $charid = strtoupper(md5(uniqid(mt_rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            .chr(125);// "}"
        return $uuid;
    }

    private function _nullToDefault($nullable, $default=''){
        if(empty($nullable)){
            return $default;
        }
        return $nullable;
    }
}