<?php

namespace TheFairLib\Utility\ParseTools;

use PHPHtmlParser\Dom;

class Parser
{
    private static $instance;

    /**
     * @return Parser
     */
    static public function Instance()
    {
        $class = get_called_class();
        if (empty(self::$instance[$class])) {
            self::$instance[$class] = new $class();
        }
        return self::$instance[$class];
    }

    /**
     * 解析Html到RAML
     * @param $content
     * @return string
     * @throws \PHPHtmlParser\Exceptions\UnknownChildTypeException
     */
    function parseToRAML($content)
    {
        $content = '<body>' . $content . '</body>';
        $preHtml = PreParser::Instance()->preHtml($content);

        $dom = new Dom;
        $dom->loadStr($preHtml, []);
        if (empty($dom->root->firstChild()->getChildren()[1])) {
            $root = $dom->root->firstChild()->getChildren()[0];
        } else {
            $root = $dom->root->firstChild()->getChildren()[1];
        }

        $result = $this->getParseHtml($root);
        $result_json = json_encode($result, JSON_UNESCAPED_UNICODE);
//        echo $result_json;
        return $result_json;
    }


    private static $HtmlNodeClassName = 'PHPHtmlParser\Dom\HtmlNode';
    private static $hTagNameArray = ['h1', 'h2', 'h3', 'h3', 'h4', 'h5', 'h6', 'h7', 'h8'];

    /**
     * parse主节点
     * @param Dom\HtmlNode $item
     * @return array
     * @throws \PHPHtmlParser\Exceptions\UnknownChildTypeException
     */
    function getParseHtml($item)
    {
        $ret = [];

        if (get_class($item) == self::$HtmlNodeClassName) {
            $children = $item->getChildren();
        } else {
            return $ret;
        }

        /**
         * @var Dom\HtmlNode $item
         */
        foreach ($children as $item) {
            $outerHtml = ParseUtils::Instance()->trimContent($item->outerHtml());
            if (empty($outerHtml)) {
                continue;
            }
            $className = get_class($item);
            if ($className == self::$HtmlNodeClassName) { // 普通html节点
                $outerHtml = ParseUtils::Instance()->trimContent($item->outerHtml());
                $tagName = strtolower($item->getTag()->name());

                if (!empty($outerHtml)) {
                    if (
                        $tagName == 'p'
                        || in_array($tagName, self::$hTagNameArray)
                        || $tagName == 'img'
                        || $tagName == 'ul'
                    ) {
                        $innerContent = $item->innerHtml();
                        $sourceContent = ParseUtils::Instance()->trimContent(strip_tags($innerContent));
                        if (!empty($sourceContent)) { // 文字类型的
                            if ($tagName == 'p') {
                                $align = ParseUtils::Instance()->getCssValueFromItem($item, 'text\-align');
                                $sourceAml = NodeBuilder::Instance()->buildTextNode($sourceContent, '', $align);
                                $this->parseMarkUps($item, $sourceAml);
                                $this->parseSentence($item, $sourceAml);
                                $ret[] = $sourceAml;
                            } else if (in_array($tagName, self::$hTagNameArray)) {
                                $sourceAml = NodeBuilder::Instance()->buildTextNode($sourceContent, $tagName);
                                $this->parseMarkUps($item, $sourceAml);
                                $ret[] = $sourceAml;
                            } else if ($tagName == 'ul') {
                                $lis = $this->getChildrenByTag($item, 'li');

                                foreach ($lis as $liitem) {
                                    $sourceContent = ParseUtils::Instance()->trimContent(strip_tags($liitem->innerHtml()));
                                    $sourceAml = NodeBuilder::Instance()->buildLi($sourceContent);
                                    $this->parseMarkUps($liitem, $sourceAml);
                                    $ret[] = $sourceAml;
                                }
                            }

                        } else if ($tagName == 'img') { // 图片类型 <img>
                            $ret[] = NodeBuilder::Instance()->buildImgNode($item);
                        } else if (strpos($item->innerHtml(), 'img') !== false) { // <p><img></img></p>
                            $imgChild = $this->getFirstChildByTag($item, 'img');
                            if ($imgChild) {
                                $ret[] = NodeBuilder::Instance()->buildImgNode($imgChild);
                            }
                        } else if ($tagName == 'p') {
                            $ret[] = NodeBuilder::Instance()->buildEmptyNode();
                        }
                    }
                }
            }
        }
        return $ret;
    }

    /**
     * @param Dom\HtmlNode $item
     * @param $childTagName
     * @return Dom\HtmlNode
     */
    function getFirstChildByTag($item, $childTagName)
    {
        $children = $item->getChildren();

        /**
         * @var Dom\HtmlNode $item
         */
        foreach ($children as $item) {
            if (get_class($item) == self::$HtmlNodeClassName) {
                $tagName = strtolower($item->getTag()->name());
                if ($tagName == $childTagName) {
                    return $item;
                }
            }
        }
        return null;
    }

    /**
     * @param Dom\HtmlNode $item
     * @param $childTagName
     * @return array
     */
    function getChildrenByTag($item, $childTagName)
    {
        $ret = [];
        $children = $item->getChildren();

        /**
         * @var Dom\HtmlNode $item
         */
        foreach ($children as $item) {
            if (get_class($item) == self::$HtmlNodeClassName) {
                $tagName = strtolower($item->getTag()->name());
                if ($tagName == $childTagName) {
                    $ret[] = $item;
                }
            }
        }
        return $ret;
    }

    /**
     * parse样式节点
     * @param Dom\HtmlNode $sourceItem
     * @param $sourceAml
     * @param Dom\HtmlNode $baseItem
     * @throws \PHPHtmlParser\Exceptions\UnknownChildTypeException
     */
    function parseMarkUps($sourceItem, &$sourceAml, $baseItem = null)
    {
        $children = $sourceItem->getChildren();

        if (empty($baseItem)) {
            $baseItem = $sourceItem;
        }

        /**
         * @var Dom\HtmlNode $item
         */
        foreach ($children as $item) {
            $outerHtml = ParseUtils::Instance()->trimContent($item->outerHtml());
            if (empty($outerHtml)) {
                continue;
            }
            $className = get_class($item);
            if ($className == self::$HtmlNodeClassName) { // 普通html节点
                $outerHtml = ParseUtils::Instance()->trimContent($item->outerHtml());
                if (!empty($outerHtml)) {
                    $tagName = strtolower($item->getTag()->name());
                    if ($tagName == 'span') {
                        $sourceAml['text']['markups'][] = NodeBuilder::Instance()->buildSpanMarkup($item, $baseItem);
                    }
                    if ($tagName == 'strong') {
                        $sourceAml['text']['markups'][] = NodeBuilder::Instance()->buildStrongMarkup($item, $baseItem);
                    }
                    $this->parseMarkUps($item, $sourceAml, $sourceItem);
                }
            }
        }
    }

    /**
     * 解析句子
     * @param Dom\HtmlNode $sourceItem
     * @param $sourceAml
     */
    function parseSentence($sourceItem, &$sourceAml)
    {
        $content = ParseUtils::Instance()->trimContent(strip_tags($sourceItem->outerHtml()));
        $result = ParseUtils::Instance()->findAll($content, '。');
        $start = 0;
        for ($index = 0; $index < sizeof($result) - 1; $index++) {
            $len = $result[$index] - $start + 1;
            $text = ParseUtils::Instance()->s_subStr($content, $start, $len);
            $sourceAml['text']['markups'][] = NodeBuilder::Instance()->buildSentence($text, $start, $len);
            $start = $result[$index] + 1;
        }
    }


}

