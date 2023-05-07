<?php

namespace app\core\elasticSearch\traits;

/**
 * @desc es query 链式封装
 * Trait QueryTrait
 * @package app\core\es\traits
 */
trait QueryTrait
{
    private $esMatchOperatorOr = 'or';
    private $esMatchOperatorAnd = 'and';
    // 设置查询参数
    private $searchField = null;
    // 查询字段
    private $source = [];
    // 设置偏移量
    private $from = 0;
    // 设置查询数量
    private $size = 0;
    // 设置排序条件
    private $sort = [];
    // 查询页
    private $page = 1;
    // 最大数量
    private $totalSize = 10000;

    /**
     * @desc 设置查询返回参数
     * @param array $source
     * @return $this
     */
    public function source(array $source = [])
    {
        $this->source = $source;
        return $this;
    }

    /**
     * @desc 等值查询
     * @param string $field
     * @param $value // 可传字符串或者数据 数组类似 in 操作
     * @return $this
     */
    public function setMustTerm(string $field, $value)
    {
        if ($field && isset($value)) {
            if (is_array($value) && count($value) > 1) {
                $this->searchField['bool']['must'][]['terms'][$field] = $value;
            } else {
                is_array($value) ? $value = array_shift($value) : '';
                $this->searchField['bool']['must'][]['term'][$field] = $value;
            }
        }
        return $this;
    }

    /**
     * @desc 模糊查询
     * @param string $field
     * @param $value
     * @return $this
     */
    public function setMustMatch(string $field, $value)
    {
        if ($field && isset($value)) {
            $this->searchField['bool']['must'][]['match'][$field] = $value;
        }
        return $this;
    }

    /**
     * @desc 多字段模糊查询
     * @param string $keyword
     * @param string $type
     * @param array $fields
     * @return $this
     */
    public function setMustAllMultiMatch(string $keyword, string $type, array $fields)
    {
        if ($fields) {
            $this->searchField['bool']['must'][]['multi_match'] = [
                'query' => $keyword,
                'type' => $type,
                'operator' => $this->esMatchOperatorAnd,
                'fields' => $fields
            ];
        }
        return $this;
    }

    /**
     * @desc 拼装 query 查询
     * @param array $query
     * @return $this
     */
    public function setMustQuery(array $query)
    {
        $this->searchField['bool']['must'][] = $query;
        return $this;
    }

    /**
     * @desc 不等于 等值查询
     * @param string $field
     * @param $value
     * @return $this
     */
    public function setMustNotTerm(string $field, $value)
    {
        if ($field && isset($value)) {
            if (is_array($value) && count($value) > 1) {
                $this->searchField['bool']['must_not'][]['terms'][$field] = $value;
            } else {
                is_array($value) ? $value = array_shift($value) : '';
                $this->searchField['bool']['must_not'][]['term'][$field] = $value;
            }
        }
        return $this;
    }

    /**
     * @desc 不等于 模糊查询
     * @param string $field
     * @param $value
     * @return $this
     */
    public function setMustNotMatch(string $field, $value)
    {
        if ($field && isset($value)) {
            $this->searchField['bool']['must_not'][]['match'][$field] = $value;
        }

        return $this;
    }

    /**
     * @desc or
     * @param string $field
     * @param $value
     * @return $this
     */
    public function setShouldMatch(string $field, $value)
    {
        if ($field && isset($value)) {
            $this->searchField['bool']['should'][]['match'][$field] = $value;
        }

        return $this;
    }

    /**
     * @desc  or
     * @param string $field
     * @param $value
     * @return $this
     */
    public function setShouldTerm(string $field, $value)
    {
        if ($field && isset($value)) {
            if (is_array($value) && count($value) > 1) {
                $this->searchField['bool']['should'][]['terms'][$field] = $value;
            } else {
                is_array($value) ? $value = array_shift($value) : '';
                $this->searchField['bool']['should'][]['term'][$field] = $value;
            }
        }

        return $this;
    }

    /**
     * @desc 范围查询
     * @param string $field
     * @param $value ['gte' => 12123212]     gte lte ge lt (代表大于等于、小于等于、大于、小于)
     * @return $this
     */
    public function setFilterRange(string $field, $value)
    {
        if ($field && isset($value)) {

            $this->searchField['bool']['filter'][]['range'][$field] = $value;
        }

        return $this;
    }

    /**
     * @desc 过滤模式
     * @param string $field
     * @param $value
     * @return $this
     */
    public function setFilterTerm(string $field, $value)
    {
        if ($field && isset($value)) {
            if (is_array($value) && count($value) > 1) {
                $this->searchField['bool']['filter'][]['terms'][$field] = $value;
            } else {
                is_array($value) ? $value = array_shift($value) : '';
                $this->searchField['bool']['filter'][]['term'][$field] = $value;
            }
        }
        return $this;
    }

    /**
     * @desc 过滤模式
     * @param string $field
     * @param $value
     * @return $this
     */
    public function setFilterMatch(string $field, $value)
    {
        if ($field && isset($value)) {
            $this->searchField['bool']['filter'][]['match'][$field] = $value;
        }

        return $this;
    }

    /**
     * @desc 设置全量查询
     * @return $this
     */
    public function setMatchAll()
    {
        //$this->searchField['match_all'] = new \stdClass();
        $this->searchField['bool']['must']['match_all'] = new \stdClass();
        return $this;
    }

    /**
     * @desc 设置从多少开始
     * @param int $from
     * @return $this
     */
    public function from(int $from)
    {
        $this->from = $from;
        return $this;
    }

    /**
     * @desc 设置数量
     * @param int $size
     * @return $this
     */
    public function size(int $size)
    {
        $this->size = $size;
        return $this;
    }

    /**
     * @desc 设置查询页数
     * @param int $page
     * @return $this
     */
    public function page(int $page)
    {
        $page = $page <= 1 ? 1 : $page;
        $this->page = $page;
        $this->from = ($this->page - 1) * $this->size;
        return $this;
    }

    /**
     * @desc 设置起始位置
     * @param int $offset
     * @return $this
     */
    public function offset(int $offset)
    {
        $this->from = $offset;
        return $this;
    }

    /**
     * @desc 设置查询数量
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit)
    {
        $this->size = $limit;
        $this->from = ($this->page - 1) * $limit;
        return $this;
    }

    /**
     * @desc 设置排序条件
     * @param array $sort 排序条件 ['id' => 'desc'];
     * @return $this
     */
    public function orderBy(array $sort)
    {
        $tempSort = [];
        foreach ($sort as $key => $value) {
            $value = is_array($value) && isset($value['order']) ? $value['order'] : $value;
            $value = strtolower($value);
            if (in_array($value, ['desc', 'asc'])) {
                $tempSort[$key] = [
                    'order' => $value,
                ];
            }
        }
        $this->sort = $tempSort;
        return $this;
    }

    //  "sort": {
    //    "_script": {
    //      "script": "Math.random()",
    //      "type": "number",
    //      "order": "asc"
    //    }
    //  }
    /**
     * @desc 随机获取数量
     * @return $this
     */
    public function random()
    {
        $tempSort = [];
        $script = [
            'script' => 'Math.random()',
            'type'   => 'number',
            'order'  => 'asc'
        ];
        $tempSort['_script'] = $script;
        $this->sort = $tempSort;
        return $this;
    }

    /**
     * 清空 筛选条件
     * @return $this
     */
    public function clearQuery()
    {
        $this->searchField = [];
        return $this;
    }

    public function getSearchField()
    {
        return $this->searchField;
    }

    public function addSuggester(string $name, $value)
    {
        $this->searchField['suggest'][$name] = $value;
        return $this;
    }
}
