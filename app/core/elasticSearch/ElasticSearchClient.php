<?php

namespace app\core\elasticSearch;

use Elasticsearch\ClientBuilder;

/**
 * @desc es 连接客户端
 * Class ElasticSearchClient
 * @package app\core\es
 */
class ElasticSearchClient
{
    //索引名
    public $index = '';
    //es链接
    protected $esClient = null;
    //设置执行参数
    protected $params = [];
    //可选参数
    protected $options = [];

    public function __construct($index = '')
    {
        $this->index = $index;
        $this->init();
    }

    public function init()
    {
        $host = explode(',', config('config.es_host'));

        $this->params['index'] = $this->index;
        $this->params['client'] = [//设置curl链接时间
            'timeout' => 10,
            'connect_timeout' => 10
        ];

        $this->esClient = ClientBuilder::create()->setHosts($host)->setRetries(5)->build();
    }

    public function setIndex(string $index)
    {
        $this->index = $index;
        $this->params['index'] = $this->index;
        return $this;
    }
}
