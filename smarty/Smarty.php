<?php
/**
 * Smarty.php
 *
 * @author ZhangHan <zhanghan@thefair.net.cn>
 * @version 1.0
 * @copyright 2015-2025 TheFair
 */
namespace TheFairLib\Smarty;

class Smarty extends \Smarty
{
    public function clear_all_assign() {
        $this->tpl_vars = array();
    }
}