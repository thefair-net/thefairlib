<?php
/**
 * DataModel.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\BigPipe;

abstract class AbstractPage extends AbstractPageLet
{

    public function __construct(Array $children = [], $tplPath = '') {
        parent::__construct($children, $tplPath);
    }
    /**
     * 通过$this->get_page_meta_data方法获取每个pl的meta_data
     * 并在此merge全局公共的meta_data
     */
    public function get_meta_data(){
        $medaData = array_merge($this->get_page_meta_data(),[
// 			"UNREADNUM" 	=> $unread,
// 			"GLOBAL_TITLE" 	=> Lang_Zh::$GlobalTitle,
// 			"GLOBAL_DESC" 	=> Lang_Zh::$GlobalDESC,
// 			"GLOBAL_KWD" 	=> Lang_Zh::$GlobalKWD,
// 			"target"		=> TARGET,
        ]);
        return $medaData;
    }

    /**
     * 每个page的meta_data在此定义为数组即可
     */
    protected function get_page_meta_data(){
        return [];
    }
}