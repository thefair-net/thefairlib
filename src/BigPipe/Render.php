<?php
/**
 * Bigpipe渲染器的基类
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\BigPipe;

use TheFairLib\BigPipe\Render\ScriptOnlyStreamlineRender;
use TheFairLib\BigPipe\Render\StreamLineRender;
use TheFairLib\BigPipe\Render\TraditionalRender;
use TheFairLib\Smarty\Adapter;
use TheFairLib\Utility\Utility;
use Yaf\Registry;

abstract class Render{
    /**
     * 当前需要渲染的根Pagelet
     *
     * @var Pagelet
     */
    protected $pl;

    /**
     * 元数据链
     *
     * @var array
     */
    protected $metaDataChain = array();

    protected $exceptions = array();

    /**
     * @var \Smarty
     */
    protected static $templateEngine = false;

    /**
     * @param Pagelet $pl
     * @param null $renderType
     * @return ScriptOnlyStreamlineRender|StreamlineRender|TraditionalRender
     * @throws Exception
     */
    final static public function create(Pagelet $pl, $renderType = null){
        if(empty($renderType)){
            $renderType	= Render::getRenderType();
        }

        switch($renderType) {
            case 'Streamline':
                return new StreamLineRender($pl);
                break;
            case 'Traditional':
                return new TraditionalRender($pl);
                break;
            case 'ScriptOnlyStreamline':
                return new ScriptOnlyStreamlineRender($pl);
                break;
            default:
                throw new Exception('Invalid render class:' . $renderType);
        }
    }

    public function __construct(Pagelet $pl = null){
        $this->pl = $pl;
    }

    static public function getRenderType(){
        $info = Prober::getClientAgent();
        if (isset($info['browser']) && $info['browser'] && !$info['robot']) {
            if(isset($_GET['__aj']) && !empty($_GET['__aj'])){
                return 'ScriptOnlyStreamline';
            }

            if(isset($_GET['__debug']) && $_GET['__debug']){
                return 'Traditional';
            }

            return 'Streamline';
        }else{
            return 'Traditional';
        }
    }

    public function setPagelet(Pagelet $pl){
        $this->pl = $pl;
        return $this;
    }

    public function getPagelet(){
        return $this->pl;
    }

    public function prepare(){
    }
    /**
     * 深度优先遍历
     *
     * @param mixed $root
     * @param callback $callbackEnter
     * @param callback $callbackLeave
     */
    static public function dfs($root, $callbackEnter, $callbackLeave){
        if(is_callable($callbackEnter)){
            call_user_func($callbackEnter, $root);
        }
        foreach ($root->getIterator() as $node){
            self::dfs($node, $callbackEnter, $callbackLeave);
        }
        if(is_callable($callbackLeave)){
            call_user_func($callbackLeave, $root);
        }
    }

    public function render(){
        self::getTemplateEngine();
        $this->prepare();
        self::dfs($this->pl, array($this, 'enter'), array($this, 'leave'));
        $this->closure();
    }

    public static function renderSinglePagelet(Pagelet $pl, $returnHtml = false){
        $meta 	= $pl->getMetaData();
        $data 	= $pl->prepareData();
        $tpl	= self::getTemplateEngine();
        $tpl->assign($meta);
        $tpl->assign($data);
        // 处理子pl
        if($pl->isSkeleton()){
            $childern = array();
            foreach ($pl->getChildren() as $cPl){
                if($cPl instanceof Pagelet){
                    $childern[$cPl->getName()] = self::renderSinglePagelet($cPl, true);
                }
            }
            $tpl->assign("pagelets", $childern);
        }
        $html 	= $tpl->fetch($pl->getTemplate());

        if($returnHtml){
            return $html;
        }else{
            echo $html;
            return null;
        }
    }

    abstract protected function enter(Pagelet $node);

    abstract protected function leave(Pagelet $node);

    public function closure(){
    }

    static protected function renderPageletWithJson(Pagelet $pl, \Smarty $tplEngine){
        $pid 	= 	isset($_GET["__d"]) && !empty($_GET["__d"]) ?
            strip_tags($_GET["__d"]) :
            $pl->getName();
        $js		= $pl->getDependsScripts();

        //结束
        return Utility::encode(
            array(
                'pid' 		=> $pid,
                'js' 		=> $js,
                'css' 		=> $pl->getDependsStyles(),
                'content' 	=> $tplEngine->fetch($pl->getTemplate())
            ));
    }

    static protected function flush(){
        if(ob_get_level()){
            ob_flush();
        }
        flush();
    }

    static protected function assignMetaChainToTemplate(\Smarty $tpl, array $metaDataChain){
        foreach ($metaDataChain as $meta){
            if($meta){
                $tpl->assign($meta);
            }
        }
    }

    /**
     * @return \Smarty
     */
    protected function getTemplateEngine(){
        if(self::$templateEngine === false){
            $config = Registry::get("config")->smarty->toArray();
            $adapter = new Adapter(null, $config);
            self::$templateEngine = $adapter->getEngine();
        }else{
            self::$templateEngine->clearAllAssign();
        }

        return self::$templateEngine;
    }

    protected function collectException(Exception $exception){
        $this->exceptions[] = $exception;
    }

    protected function processExceptions(){
        if($this->exceptions){
            throw $this->exceptions[0];
        }
    }
}