<?php
/**
 * Sphinx.php
 *
 * @author liumingzhi
 * @version 1.0
 * @copyright 2015-2015
 * @date 16/2/26 上午11:18
 */

namespace TheFairLib\Search\Sphinx;

use TheFairLib\Config\Config;
use TheFairLib\Search\Exception;

/**
 * SPH_MATCH_ALL    匹配所有查询词（默认模式）.
 * SPH_MATCH_ANY    匹配查询词中的任意一个.
 * SPH_MATCH_PHRASE    将整个查询看作一个词组，要求按顺序完整匹配.
 * SPH_MATCH_BOOLEAN    将查询看作一个布尔表达式.
 * SPH_MATCH_EXTENDED    将查询看作一个Sphinx内部查询语言的表达式.
 * SPH_MATCH_FULLSCAN    使用完全扫描，忽略查询词汇.
 * SPH_MATCH_EXTENDED2    类似 SPH_MATCH_EXTENDED ，并支持评分和权重.
 */
class Sphinx
{

    static public $instance;

    static public $server = 'default';

    //默认服务器
    static private $host = '127.0.0.1';

    //默认端口
    static private $port = 9312;

    //超时15秒
    protected $timeout = 15;

    protected $_data = [];

    protected $_keywords = '';

    protected $_indexName = '';

    private $_page = [
        'page' => 1,
        'page_count' => 1,
        'item_count' => 0,
        'item_per_page' => 20,
        'item_list' => [],
    ];

    /**
     * @var \SphinxClient
     */
    private $conn;

    /**
     * @return Sphinx
     */
    static public function Instance()
    {
        $class = get_called_class();
        if (empty(self::$instance)) {
            self::$instance = new $class();
            $config = Config::get_search_sphinx(self::$server);
            self::$host = $config['host'];
            self::$port = $config['port'];
        }
        return self::$instance;
    }

    /**
     * 建立链接
     *
     * @return Sphinx
     */
    public function init()
    {
        $this->conn = new \SphinxClient();
        $this->conn->setServer(self::$host, self::$port);
        $this->conn->setMatchMode(SPH_MATCH_ANY);//匹配查询词中的任意一个.
        $this->conn->setMaxQueryTime($this->timeout);
        $this->conn->SetArrayResult(true);//控制搜索结果集的返回格式
        return $this;
    }

    /**
     * 执行搜索查询
     *
     * @param $keyword
     * @param $indexName
     * @return $this
     * @throws Exception
     */
    public function query($keyword, $indexName)
    {
        if (empty($keyword) || empty($indexName)) {
            throw new Exception('query error: keyword or indexName is null');
        }
        $this->_keywords = $keyword;
        $this->_indexName = $indexName;
        return $this;
    }

    /**
     * 字段
     *
     * @param string $field
     * @return $this
     */
    public function field($field = '*')
    {
        $this->conn->SetSelect($field);
        return $this;
    }

    public function getError()
    {
        return $this->conn->GetLastError();
    }

    /**
     * 给服务器端结果集设置一个偏移量（$offset）和从那个偏移量起向客户端返回的匹配项数目限制（$limit）。
     * 并且可以在服务器端设定当前查询的结果集大小（$max_matches），另有一个阈值（$cutoff），当找到的匹配项达到这个阀值时就停止搜索。
     * 全部这些参数都必须是非负整数。
     * $page是需要获取的页数
     * @param $page
     * @param int $itemPerPage
     * @param int $maxMatches
     * @param int $cutoff
     * @return $this
     */
    public function limit($page, $itemPerPage = 20, $maxMatches = 1000, $cutoff = 0)
    {
        $this->setPage();
        $this->_page['page'] = $page;
        $offset = ($page - 1) * $itemPerPage;
        $this->_page['item_per_page'] = $itemPerPage;
        $this->conn->SetLimits($offset, $this->_page['item_per_page'], $maxMatches, $cutoff);
        return $this;
    }

    /**
     * 排序
     *
     * @param $field
     * @param string $orderBy
     * @return $this
     * @throws Exception
     */
    public function order($field, $orderBy = 'asc')
    {
        if (empty($field)) {
            throw new Exception('order error: field is null');
        }
        $this->conn->SetSortMode(SPH_SORT_EXTENDED, "{$field} $orderBy");
        return $this;
    }
    public function sortMode($mode,$sortBy)
    {
        if (empty($mode)) {
            throw new Exception('order error: field is null');
        }
        $this->conn->SetSortMode($mode, $sortBy);
        return $this;
    }
    public function indexWeight($weight){
        $this->conn->SetIndexWeights($weight);
        return $this;
    }
    public function match($desc = SPH_MATCH_ALL)
    {
        switch ($desc) {
            case SPH_MATCH_ALL :
                $this->conn->SetMatchMode(SPH_MATCH_ALL);
                break;
            case SPH_MATCH_ANY:
                $this->conn->SetMatchMode(SPH_MATCH_ANY);
                break;
            case SPH_MATCH_PHRASE :
                $this->conn->SetMatchMode(SPH_MATCH_PHRASE);
                break;
            case SPH_MATCH_BOOLEAN :
                $this->conn->SetMatchMode(SPH_MATCH_BOOLEAN);
                break;
            case SPH_MATCH_EXTENDED :
                $this->conn->SetMatchMode(SPH_MATCH_EXTENDED);
                break;
            case SPH_MATCH_FULLSCAN :
                $this->conn->SetMatchMode(SPH_MATCH_FULLSCAN);
                break;
            default :
                $this->conn->SetMatchMode(SPH_MATCH_EXTENDED2);
                break;
        }
        return $this;
    }

    public function ranking($desc = SPH_RANK_PROXIMITY_BM25)
    {
        switch ($desc) {
            case SPH_RANK_PROXIMITY_BM25 :
                $this->conn->SetRankingMode(SPH_RANK_PROXIMITY_BM25);
                break;
            case SPH_RANK_BM25 :
                $this->conn->SetRankingMode(SPH_RANK_BM25);
                break;
            case SPH_RANK_NONE :
                $this->conn->SetRankingMode(SPH_RANK_NONE);
                break;
            default :
                $this->conn->SetRankingMode(SPH_RANK_PROXIMITY_BM25);
                break;
        }
        return $this;
    }

    /**
     * 获得数据
     *
     * @param bool $filter
     * @return array
     */
    public function get($filter = true)
    {
        $this->_data = $this->conn->query($this->_keywords, $this->_indexName);
        return $filter ? $this->_filterData() : $this->_data;
    }

    /**
     * 清空数据，以便二次查询
     */
    public function setPage()
    {
        $this->_page = [
            'page' => 1,
            'page_count' => 1,
            'item_count' => 0,
            'item_per_page' => 20,
            'item_list' => [],
        ];
    }

    /**
     * 过滤结果数据
     *
     * @return array
     */
    private function _filterData()
    {
        $itemList = [];
        if (!empty($this->_data['total']) && !empty($this->_data['matches'])) {
            $data = array_values($this->_data['matches']);
            foreach ($data as $value) {
                $itemList[] = array_merge(['id' => $value['id']], $value['attrs']);
            }
            $itemPerPage = min(50, $this->_page['item_per_page']);
            $pageCount = ceil($this->_data['total'] / $itemPerPage);
            $this->_page['page_count'] = $pageCount;
            $this->_page['item_count'] = $this->_data['total'];
            $this->_page['item_list'] = $itemList;
            $this->_page['words'] = $this->_data['words'];
        }
        $this->_page['words'] = $this->_data['words'];
        return $this->_data = $this->_page;
    }
}