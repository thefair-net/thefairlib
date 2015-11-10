<?php
/**
 * Bigpipe渲染器的基类
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\BigPipe;

use TheFairLib\Smarty\Smarty;

abstract class Render{
    static protected $templateEngineClass = 'Smarty';

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

    final static public function create(Pagelet $pl, $renderType = null){
        if(empty($renderType)){
            $renderType	= Render::getRenderType();
        }

        $renderClass = '\\TheFairLib\\BigPipe\\Render\\' . ucfirst($renderType) . 'Render';
        if(!class_exists($renderClass) || !is_subclass_of($renderClass, __CLASS__)){
            if(class_exists($renderType) && is_subclass_of($renderType, __CLASS__)){
                $renderClass = $renderType;
            }
            throw new Exception('Invalid render class:' . $renderType);
        }

        return new $renderClass($pl);
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
// 			if($info['browser'] == 'Internet Explorer' && !strpos(Lib_Client_Prober::$user_agent, "MSIE 10.0") && !strpos(Lib_Client_Prober::$user_agent, "MSIE 9.0") && !strpos(Lib_Client_Prober::$user_agent, "MSIE 8.0") && !strpos(Lib_Client_Prober::$user_agent, "MSIE 7.0")) {
// 				return 'Traditional';
// 			}

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
        $this->templateEngine = new self::$templateEngineClass();
        $this->prepare();
        self::dfs($this->pl, array($this, 'enter'), array($this, 'leave'));
        $this->closure();
    }

    public static function renderSinglePagelet(Pagelet $pl, $returnHtml = false){
        $meta 	= $pl->getMetaData();
        $data 	= $pl->prepareData();
        $tpl	= new Lib_Smarty2();
        $tpl->assign($meta);
        $tpl->assign($data);
        // 处理子pl
        if($pl->isSkeleton()){
            $childern = array();
            foreach ($pl->getChildren() as $cPl){
                $childern[$cPl->getName()] = self::renderSinglePagelet($cPl, true);
            }
            $tpl->assign("pagelets", $childern);
        }
        $html 	= $tpl->fetch($pl->getTemplate());

        if($returnHtml){
            return $html;
        }else{
            echo $html;
        }
    }

    abstract protected function enter(Pagelet $node);

    abstract protected function leave(Pagelet $node);

    public function closure(){
    }

    static protected function renderPageletWithJson(Pagelet $pl, $tplEngine){
        $pid 	= 	isset($_GET["__d"]) && !empty($_GET["__d"]) ?
            strip_tags($_GET["__d"]) :
            $pl->getName();
        $js		= $pl->getDependsScripts();

        //结束
        return json_encode(
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

    static protected function assignMetaChainToTemplate($tpl, array $metaDataChain){
        foreach ($metaDataChain as $meta){
            if($meta){
                $tpl->assign($meta);
            }
        }
    }

    protected function getTemplateEngine(){
        $this->templateEngine->clear_all_assign();
        return $this->templateEngine ? $this->templateEngine : new self::$templateEngine_class();
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