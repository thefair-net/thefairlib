<?php
/**
 * DataModel.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\BigPipe;

use TheFairLib\Config\Config;
use TheFairLib\Utility\Utility;

abstract class AbstractPageLet extends PageLet
{
    public $showLoading 	= false;
    protected $tpl 			= '';
    protected $plGlobals 	= [];
    protected $name 		= '';
    protected $isSkeleton 	= false;
    protected static $_params;

    public function __construct($tplPath = '') {
        self::$_params = Utility::get_requset_params();
        if(empty($this->name)){
            $this->name = strtolower(get_class($this));
        }

        $this->setTemplate($this->_getTplPath($tplPath));

        $this->setDependsScripts();
        $this->setDependsStyles();
        parent::__construct($this->name, $this->_getChildren());
    }

    /**
     * @return array $children
     */
    abstract protected function _getChildren();

    public function getGlobalMetaData(){
        $medaData = array_merge($this->getPlGlobalData(), $this->plGlobals);
        if(!empty($medaData["PAGE_TITLE"])){
            $medaData["PAGE_TITLE"] = Utility::utf8SubStr($medaData["PAGE_TITLE"], 80);
        }
        if(!empty($medaData["PAGE_DESC"])){
            $medaData["PAGE_DESC"] = Utility::utf8SubStr($medaData["PAGE_DESC"], 90);
        }
        if(!empty($medaData["PAGE_KWD"])){
            $medaData["PAGE_KWD"] = Utility::utf8SubStr($medaData["PAGE_KWD"], 80);
        }
        return $medaData;
    }

    protected function getPlGlobalData(){
        $global_data =array();
        return $global_data;
    }

    protected function setPlGlobal($key, $value){
        if(!empty($value)){
            $this->plGlobals[$key] = $value;
        }else{
            if(isset($this->plGlobals[$key])){
                unset($this->plGlobals[$key]);
            }
        }
    }

    protected function setTitle($title, $type = "PAGE_TITLE"){
        $this->setPlGlobal($type, $title);
    }

    protected function setDesc($description, $type = "PAGE_DESC"){
        $this->setPlGlobal($type, $description);
    }

    protected function setKwd($keywords, $type = "PAGE_KWD"){
        $this->setPlGlobal($type, $keywords);
    }

    protected function message($msg, $redirect='/', $timeout=3){
        $_ENV['errmsg'] = array(
            'msg'=>$msg,
            'redirect'=>$redirect,
            'timeout'=>$timeout,
        );
        $this->end(new Exception($msg));
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
        echo "<script>BigPipe && BigPipe.onPageletArrive(".Utility::encode($pl).")</script>\n";
    }

    public function setDependsScripts($scripts = []) {
        $config = Config::get_bigpipe_scripts(str_replace('_', '.', $this->name));
        if(empty($config) || !is_array($config)){
            $config = [];
        }
        $this->scripts = array_merge($scripts, $config);
    }

    public function setDependsStyles($styles = []) {
        $config = Config::get_bigpipe_styles(str_replace('_', '.', $this->name));
        if(empty($config) || !is_array($config)){
            $config = [];
        }
        $this->styles = array_merge($styles, $config);
    }

    protected function _getTplPath($tplPath){
        if(empty($tplPath)){
            $tplPath = str_replace("_", DIRECTORY_SEPARATOR, get_class($this));
        }else{
            $tplPath = str_replace('\\', DIRECTORY_SEPARATOR, $tplPath);
        }

        $pathAry = pathinfo($tplPath);
        return ucfirst(strtolower($pathAry['dirname'])).DIRECTORY_SEPARATOR.lcfirst($pathAry['basename']).'.tpl';
    }
}