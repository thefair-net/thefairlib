<?php
/**
 * Smarty Internal Plugin Compile Append
 * Compiles the {asset} tag
 *
 * @package    Smarty
 * @subpackage Compiler
 * @author     liumingzhij26
 */

/**
 * Smarty Internal Plugin Compile Append Class
 *
 * @package    Smarty
 * @subpackage Compiler
 */
class Smarty_Internal_Compile_Asset extends Smarty_Internal_Compile_Assign
{
    /**
     * Compiles code for the {asset} tag
     *
     * @param array $args
     * @param object $compiler
     * @param array $parameter
     * @return string
     * @throws \Yaf\Exception
     */
    public function compile($args, $compiler, $parameter)
    {
        $this->required_attributes = array('url');
        $_attr = $this->getAttributes($compiler, $args);
        $config = \TheFairLib\Config\Config::get_app();
        if (empty($config)) {
            throw new \Yaf\Exception('Smarty, asset, config/App.php not null');
        }
        if ($config['phase'] == 'rd') {
            $rand = time();
        } else {
            $rand = isset($config['static']['resource_time']) ? $config['static']['resource_time'] : date('Ymd');
        }
        foreach ($_attr as $value) {
            if (!empty($value)) {
                $value = trim(trim($value, "\""), "'");
                return $config['static']['resource_host'] . $value . "?v=" . $rand;
            }
        }
        throw new \Yaf\Exception('Smarty, asset, error');
    }
}
