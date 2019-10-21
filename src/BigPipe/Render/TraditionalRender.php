<?php
/**
 * 原始页面模式输出
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\BigPipe\Render;
use TheFairLib\BigPipe\Exception;
use TheFairLib\BigPipe\Pagelet;
use TheFairLib\BigPipe\Render;

class TraditionalRender extends Render{
    protected $scripts = array();
    protected $styles = array();
    protected $plContents = array();

    protected $exceptions = array();
    protected $globalMetaData = array();//pl向page传递公共信息

    protected function enter(Pagelet $pl){
        $this->metaDataChain[] = $pl->getMetaData();
        $this->scripts = array_merge($this->scripts, $pl->getDependsScripts());
        $this->styles = array_merge($this->styles, $pl->getDependsStyles());
    }

    protected function leave(Pagelet $pl){
        $tplEngine = $this->getTemplateEngine();

        self::assignMetaChainToTemplate($tplEngine, $this->metaDataChain);
        $this->prepareGlobalData();
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
        $tplEngine->assign('pagelets', $this->plContents);
        if($pl === $this->pl){
            $tplEngine->assign('pagelet_scripts', $this->scripts);
            $tplEngine->assign('pagelet_styles', $this->styles);
            $tplEngine->display($pl->getTemplate());
        }else{
            try {
                $html = $tplEngine->fetch($pl->getTemplate());
            } catch (Exception $e) {
                $this->collectException($e);
                $html = '';
            }
            $this->plContents[$pl->getName()] = $html;
        }
        //pop meta chain.
        array_pop($this->metaDataChain);
    }

    public function prepareGlobalData(){
        $metaData	= is_array($this->pl->getGlobalMetaData()) ? array($this->pl->getGlobalMetaData()) : array();
        $children	= $this->pl->getChildren();
        if(!empty($children)){
            foreach($children as $child){
                if($child instanceof Pagelet){
                    $childMeta = is_array($child->getGlobalMetaData()) ? array($child->getGlobalMetaData()) : array();
                    $metaData = array_merge($metaData, $childMeta);
                }
            }
        }
        $this->globalMetaData = array_merge($metaData, $this->globalMetaData);
    }

    public function closure(){
        $this->processExceptions();
    }
}