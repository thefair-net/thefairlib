<?php
/**
 * 流水线方式仅输出script
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\BigPipe\Render;

use TheFairLib\BigPipe\Pagelet;
use TheFairLib\Utility\Utility;

class ScriptOnlyStreamlineRender extends StreamlineRender{
    protected $skeletonMetaData = array();
    /**
     * 针对单个pl请求的处理
     * @see Lib_Bigpipe_StreamlineRender::enter()
     */
    protected function enter(Pagelet $pl){
        if(!$pl->isSkeleton() && !empty($_GET["__pl"]) && $pl->getName() != strip_tags($_GET["__pl"])){
            return;
        }
        parent::enter($pl);
    }

    public function prepare(){
        $metaData	= is_array($this->pl->getMetaData()) ? array($this->pl->getMetaData()) : array();
        $this->skeletonMetaData = array_merge($metaData, $this->skeletonMetaData);
    }

    protected function renderSkeletonPagelet(Pagelet $pl){
        // 不要加任何操作
    }

    protected function renderStreamPagelet(Pagelet $pl, $metaDataChain){
        $metaDataChain = array_merge($this->skeletonMetaData, $metaDataChain);
        parent::renderStreamPagelet($pl, $metaDataChain);
    }

    protected function surroundWithScriptTag($string){
        $jsonAry 	= Utility::decode($string);
        $jsonAry["js"] = array();
        $string		= Utility::encode($jsonAry);
        $callback 	= !empty($_GET["__cb"]) ? strip_tags($_GET["__cb"]) : "BigPipe && BigPipe.onPageletArrive";
        return $_GET["__no_cb"] == 1 ? $string : "$callback({$string});\n";
    }

    public function closure(){
        $this->renderDeferedPagelets();
        $this->processExceptions();
    }
}