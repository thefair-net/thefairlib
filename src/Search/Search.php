<?php
/**
 * Search.php
 *
 * @author liumingzhi
 * @version 1.0
 * @copyright 2015-2015
 * @date 16/2/26 上午11:12
 */
namespace TheFairLib\Search;

use TheFairLib\Search\Sphinx\Sphinx;

class Search
{

    static public $instance;

    private $_server = 'sphinx';

    /**
     * @return Search
     */
    static public function Instance()
    {
        $class = get_called_class();
        if (empty(self::$instance)) {
            self::$instance = new $class();
        }
        return self::$instance;
    }

    /**
     * @return Sphinx
     * @throws Exception
     */
    public function getApplication()
    {
        switch ($this->_server) {
            case 'sphinx' :
                return Sphinx::Instance()->init();
                break;
        }
        throw new Exception('error ', $this->_server);
    }

}