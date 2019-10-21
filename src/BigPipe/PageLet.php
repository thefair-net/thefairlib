<?php
/**
 * Bigpipe的pagelet的基类
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\BigPipe;
class PageLet implements \ArrayAccess,\IteratorAggregate  {
    static private $pageletNames = array();
    private $name = '';
    /**
     * @var array
     */
    protected $children = array ();
    protected $data = array ();
    protected $tpl = '';
    protected $scripts = array();
    protected $styles = array();
    protected $isSkeleton = false;

    public $showLoading = true;
    /**
     * @var \ArrayIterator
     */
    protected $iterator;

    public function __construct($name, array $children = array()) {
        $this->setName($name);
        foreach ($children as $child){
            $this->addChild($child);
        }
        return;
    }

    public function getClassName() {
        return strtolower ( get_class($this) );
    }

    public function getName(){
        return $this->name;
    }

    public function isSkeleton(){
        return $this->isSkeleton;
    }

    public function addChild(Pagelet $child){
        if($this->offsetExists($child->getName())){
            throw new Exception('pl added already');
        }
        $this->offsetSet($child->getName(), $child);
    }

    public function getChild($name) {
        return $this->offsetGet($name);
    }

    public function getChildren(){
        return $this->children;
    }

    public function getChildrenNames(){
        return array_keys($this->children);
    }

    public function delChild($name){
        return $this->offsetUnset($name);
    }

    public function getTemplate() {
        if(!$this->tpl){
            throw new Exception('tpl not set');
        }
        return $this->tpl;
    }

    public function setTemplate($tpl){
        $this->tpl = $tpl;
        return $this->getTemplate();
    }

    public function getMetaData(){
        return [];
    }

    public function getGlobalMetaData(){
        return [];
    }

    public function prepareData() {
        return [];
    }

    public function getDependsScripts() {
        return $this->scripts;
    }

    public function getDependsStyles() {
        return $this->styles;
    }

    public function offsetExists($offset) {
        return isset($this->children[$offset]);
    }

    public function offsetGet($offset) {
        return $this->offsetExists($offset) ? $this->children[$offset] : null;
    }

    public function offsetSet($offset, $value) {
        if(!$this->isSkeleton() && $value->isSkeleton()){
            throw new Exception('Skeleton pagelet cannot added to a non-skeleton parent');
        }
        return $this->children[$offset] = $value;
    }

    public function offsetUnset($offset) {
        unset($this->children[$offset]);
    }

    public function getIterator($forceNew = false) {
        if($this->iterator === NULL || $forceNew){
            $this->iterator = new \ArrayIterator($this->children);
        }
        return $this->iterator;
    }

    public function rewindIterator($recursive = false){
        if(!$this->iterator){
            return;
        }
        $this->iterator->rewind();
        if($recursive){
            foreach ($this->iterator as $child){
                $child->getIterator()->rewindIterator($recursive);
            }
            $this->iterator->rewind();
        }
        return;
    }

    private function setName($name){
        if(isset(self::$pageletNames[$name])){
            throw new Exception('pagelets name cannot be dunplicated:'.$name);
        }
        self::$pageletNames[$name] = $name;
        $this->name = $name;
    }

    public function end(Exception $e){

    }
}