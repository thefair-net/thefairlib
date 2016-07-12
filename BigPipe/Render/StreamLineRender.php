<?php
/**
 * 流水线方式输出
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\BigPipe\Render;

use TheFairLib\BigPipe\Exception;
use TheFairLib\BigPipe\Pagelet;
use TheFairLib\BigPipe\Render;
use TheFairLib\Utility\Utility;

class StreamLineRender extends Render{
    protected $deferedPagelets = array();
    protected $scripts = array();
    protected $skeletonScripts = array();
    protected $styles = array();
    protected $plContents = array();
    protected $globalMetaData = array();//pl向page传递公共信息

    protected function enter(Pagelet $pl){
        $this->metaDataChain[] = $pl->getMetaData();
        if(!$pl->isSkeleton()){
            $this->deferedPagelets[] = array($pl, $this->metaDataChain);
            return;
        }

        $pl->getDependsScripts() AND $this->skeletonScripts[$pl->getName()] = $pl->getDependsScripts();
        $this->scripts = array_merge($this->scripts, $pl->getDependsScripts());
        $this->styles = array_merge($this->styles, $pl->getDependsStyles());
    }

    protected function leave(Pagelet $pl){
        if($pl->isSkeleton()){
            $this->renderSkeletonPagelet($pl);
        }
        // metaData只会对自己的子pl（自己的叶节点）共享（多级继承），而不会影响其它
        array_pop($this->metaDataChain);
    }

    /**
     * 从html中删除</html>结束标签。
     *
     * 如果html结束标签出现在尾部(最后的20字节之内)，则移除之。否则，会保留，以防止替换掉不该替换的标签。
     *
     * @param string $html
     * @return string
     */
    protected function moveOutHtmlCloseTag($html){
        $htmlCloseTagPos = strripos($html, '</html>');
        if($htmlCloseTagPos !== false && abs(strlen($html) - $htmlCloseTagPos) <= 20){
            $html = substr_replace($html, '', $htmlCloseTagPos, 7);
        }
        return $html;
    }

    protected function renderSkeletonPagelet(Pagelet $pl){
        $children = array_fill_keys($pl->getChildrenNames(), '');
        if(!empty($this->globalMetaData["pagelets"])){
            $children = array_merge($children, $this->globalMetaData["pagelets"]);
            unset($this->globalMetaData["pagelets"]);
        }
        $tplEngine = $this->getTemplateEngine();

        self::assignMetaChainToTemplate($tplEngine, $this->metaDataChain);
        self::assignMetaChainToTemplate($tplEngine, $this->globalMetaData);
        try{
            $tplEngine->assign($pl->prepareData());
        }catch (Exception $ex){
            $this->collectException($ex);
            if($pl === $this->pl){
                //output blank
            }else{
                $this->plContents[$pl->getName()] = '';
            }
            return ;
        }
        $tplEngine->assign('pagelets', array_merge($children, $this->plContents));
        // ==时为根节点，反之非根节点
        if($pl === $this->pl){
            $tplEngine->assign('pagelet_scripts', $this->scripts);
            $tplEngine->assign('pagelet_styles', $this->styles);
            $html = $tplEngine->fetch($pl->getTemplate());
            echo $this->moveOutHtmlCloseTag($html);
            self::flush();
        }else{
            $this->plContents[$pl->getName()] = $tplEngine->fetch($pl->getTemplate());
        }
    }

    protected function renderStreamPagelet(Pagelet $pl, $metaDataChain){
        //防止模板里出现undefined index
        $children = array_fill_keys($pl->getChildrenNames(), '');

        $tplEngine = $this->getTemplateEngine();

        self::assignMetaChainToTemplate($tplEngine, $metaDataChain);
        try{
            $tplEngine->assign($pl->prepareData());
            $tplEngine->assign('pagelets', $children);
            $tplEngine->assign('pagelet_scripts', $pl->getDependsScripts());
            $tplEngine->assign('pagelet_styles', $pl->getDependsStyles());
            //echo "<script>console.log('".$pl->getName().":'+new Date().getTime());</script>\n";
            echo $this->surroundWithScriptTag(self::renderPageletWithJson($pl, $tplEngine));
            self::flush();
        }catch (Exception $ex){
// 			$this->collectException($ex);
            $pl->end($ex);
        }
    }

    protected function renderScriptWithJson($plName, $scripts){
        return Utility::encode(array('pid' => $plName, 'js' => $scripts));
    }

    protected function renderSkeletonScripts(){
        foreach($this->skeletonScripts as $plName => $scripts){
            if($plName != $this->pl->getName()){
                echo $this->surroundWithScriptTag($this->renderScriptWithJson($plName, $scripts));
            }
        }
        self::flush();
    }

    protected function renderDeferedPagelets(){
        while ($this->deferedPagelets){
            list($pl, $metaDataChain) = array_shift($this->deferedPagelets);
            $this->renderStreamPagelet($pl, $metaDataChain);
        }
    }

    protected function surroundWithScriptTag($string){
        return "<script>BigPipe && BigPipe.onPageletArrive({$string})</script>\n";
    }

    public function prepare(){
        $metaData	= is_array($this->pl->getGlobalMetaData()) ? array($this->pl->getGlobalMetaData()) : array();
        $children	= $this->pl->getChildren();
        if(!empty($children)){
            foreach($children as $child){
                if($child instanceof Pagelet){
                    $childMeta = is_array($child->getGlobalMetaData()) ? array($child->getGlobalMetaData()) : array();
                    $metaData = !empty($childMeta) ? array_merge($metaData, $childMeta) : $metaData;
                    //判断是否显示自动loading
                    if($child->showLoading === true){
                        $metaData["pagelets"][$child->getName()] = "<script>BigPipe && BigPipe.autoLoading('{$child->getName()}', 500)</script>";
                    }
                }
            }
        }
        $this->globalMetaData = array_merge($metaData, $this->globalMetaData);
    }

    /**
     * bigpipe的streamline方式最后处理需要顺序输出的pagelets
     */
    public function closure(){
        $this->renderSkeletonScripts();
        $this->renderDeferedPagelets();
        //输出之前过滤掉的闭合标签
        echo "</html>";
        $this->processExceptions();
    }
}