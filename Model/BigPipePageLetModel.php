<?php
/**
 * DataModel.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Model;

use TheFairLib\BigPipe\Pagelet;
use TheFairLib\Utility\Utility;

abstract class BigPipePageLetModel extends Pagelet
{
    public $showLoading 	= false;
    protected $tpl 			= '';
    protected $plGlobals 	= [];
    protected $name 		= '';
    protected $isSkeleton 	= false;


    public function __construct(Array $children = array()) {
        if(empty($this->name)){
            $this->name = strtolower(get_class($this));
        }

        $this->tpl = strtolower(str_replace("_", "/", get_class($this))).'.html';
        parent::__construct($this->name, $children);
    }

    public function get_global_meta_data(){
        $meda_data = array_merge($this->get_pl_global_data(), $this->plGlobals);
        if(!empty($meda_data["PAGE_TITLE"])){
            $meda_data["PAGE_TITLE"] = Utility::utf8SubStr($meda_data["PAGE_TITLE"], 80);
        }
        if(!empty($meda_data["PAGE_DESC"])){
            $meda_data["PAGE_DESC"] = Utility::utf8SubStr($meda_data["PAGE_DESC"], 90);
        }
        if(!empty($meda_data["PAGE_KWD"])){
            $meda_data["PAGE_KWD"] = Utility::utf8SubStr($meda_data["PAGE_KWD"], 80);
        }
        return $meda_data;
    }

    protected function get_pl_global_data(){
        $global_data =array();
        return $global_data;
    }

    protected function set_pl_global($key, $value){
        if(!empty($value)){
            $this->plGlobals[$key] = $value;
        }else{
            if(isset($this->plGlobals[$key])){
                unset($this->plGlobals[$key]);
            }
        }
    }

    protected function set_title($title, $type = "PAGE_TITLE"){
        $this->set_pl_global($type, $title);
    }

    protected function set_desc($description, $type = "PAGE_DESC"){
        $this->set_pl_global($type, $description);
    }

    protected function set_kwd($keywords, $type = "PAGE_KWD"){
        $this->set_pl_global($type, $keywords);
    }

    protected function message($msg, $redirect='/', $timeout=3){
        $_ENV['errmsg'] = array(
            'msg'=>$msg,
            'redirect'=>$redirect,
            'timeout'=>$timeout,
        );
        $this->end();
    }

    public function end(Exception $e){
        $errData = $e->getData();
        $html = '<div class="alert alert-error">
		<button type="button" class="close" data-dismiss="alert">×</button>
		<strong>【'.$this->name.'】 Error</strong>
		<p>'.$errData["message"].'</p>
		</div>';
        $pl = array(
            "pid" => $this->name,
            "js" => array(),
            "css" => array(),
            "content" => $html
        );
        echo "<script>BigPipe && BigPipe.onPageletArrive(".json_encode($pl).")</script>\n";
    }

}