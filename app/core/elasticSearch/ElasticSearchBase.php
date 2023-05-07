<?php

namespace app\core\elasticSearch;

use app\core\elasticSearch\traits\QueryTrait;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use think\facade\Log;

/**
 * @desc es 基础操作类
 * Class ElasticSearchBase
 * @package app\core\es
 */
class ElasticSearchBase extends ElasticSearchClient
{
    use QueryTrait;

    /**
     * @desc 搜索文档
     * @return array|callable
     */
    public function search()
    {
        $this->setBoolQuery();
        $params = $this->params;
        $params['track_total_hits'] = true;
        $params['_source'] = $this->source;
        $params['body']['from'] = $this->from;
        $params['body']['size'] = $this->size <= 0 ? $this->totalSize : $this->size;
        $params['body']['sort'] = $this->sort;
        try {
            $result = $this->esClient->search($params);
        } catch (\Exception $e) {
            Log::error(sprintf('ElasticSearchBase search errorMsg %s errorCode %d errorLine %d',
                $e->getMessage(), $e->getCode(), $e->getLine()));
            return [];
        }
        return $this->formatSearchData($result);
    }

    /**
     * @desc 获取一条文档（不带分页查询）
     * @return array|mixed
     */
    public function one()
    {
        $this->setBoolQuery();
        $params = $this->params;
        $params['_source'] = $this->source;
        $params['body']['from'] = 0;
        $params['body']['size'] = 1;
        $params['body']['sort'] = $this->sort;
        try {
            $result = $this->esClient->search($params);
        } catch (\Exception $e) {
            Log::error(sprintf('ElasticSearchBase one errorMsg %s errorCode %d errorLine %d',
                $e->getMessage(), $e->getCode(), $e->getLine()));
            return [];
        }

        return $this->formatSearchData($result);
    }

    /**
     * @desc 获取文档（不带分页查询）
     * @return array
     */
    public function all()
    {
        $this->setBoolQuery();
        $params = $this->params;
        $params['_source'] = $this->source;
        $params['track_total_hits'] = true;
        $params['body']['sort'] = $this->sort;
        $params['body']['from'] = $this->from <= 0 ? 0 : $this->from;
        $params['body']['size'] = $this->size <= 0 ? $this->totalSize : $this->size;
        try {
            $result = $this->esClient->search($params);
        } catch (\Exception $e) {
            Log::error(sprintf('ElasticSearchBase all errorMsg %s errorCode %d errorLine %d',
                $e->getMessage(), $e->getCode(), $e->getLine()));
            return [];
        }

        return $this->formatSearchData($result);
    }

    /**
     * @desc 查询之后格式化显示数据（子类可重载）
     * @param array $esResponseData
     * @return array
     */
    public function formatSearchData(array $esResponseData): array
    {
        $result = [
            'total' => $esResponseData['hits']['total']['value'] ?? 0,
            'took' => $esResponseData['took'] ? ($esResponseData['took'] . ' ms') : ('0 ms'),
            'data' => [],
        ];
        if (isset($esResponseData['hits']['hits']) && $esResponseData['hits']['hits']) {
            foreach ($esResponseData['hits']['hits'] as $elasticResult) {
                $data = $elasticResult['_source'];
//                $data['id'] = $elasticResult['_id'];
//                $data['score'] = $elasticResult['_score'];
//                $data['distance'] = isset($elasticResult['sort']) ? current($elasticResult['sort']) : 0;
                $result['data'][] = $data;
            }
        }
        return $result;
    }

    /**
     * @desc 统计文档数量
     * @return array|callable
     */
    public function count()
    {
        $this->setBoolQuery();
        $params = $this->params;
        return $this->esClient->count($params);
    }

    /**
     * @desc 获取单个文档
     * @param $id
     * @return array
     */
    public function find($id): array
    {
        $params = [
            'index' => $this->index,
            'id' => $id
        ];
        try {
            $data = $this->esClient->get($params);
        } catch (Missing404Exception $e) {
            Log::error(sprintf('ElasticSearchBase find missing404 errorMsg %s errorCode %d errorLine %d',
                $e->getMessage(), $e->getCode(), $e->getLine()));
            return [];
        } catch (\Exception $e) {
            Log::error(sprintf('ElasticSearchBase find errorMsg %s errorCode %d errorLine %d',
                $e->getMessage(), $e->getCode(), $e->getLine()));
            return [];
        }
        return $data['_source'] ?? [];
    }

    /**
     * @desc 新增或修改文档
     * @param $id
     * @param array $data
     * @return bool
     */
    public function doCreateOrUpdate($id, array $data): bool
    {
        Log::info(sprintf('ElasticSearchBase doCreateOrUpdate id=%d $data=%s', $id, json_encode($data)));
        $params = [
            'index' => $this->index,
            'id' => $id,
        ];

        $paramsBody = $params + [
                'body' => $data
            ];
        try {
            $hasExist = $this->esClient->exists($params);
            if ($hasExist) {
                //更新
                $paramsBody['body'] = ['doc' => $data];
                $paramsBody['retry_on_conflict'] = 5;
                $this->esClient->update($paramsBody);
            } else {
                $this->esClient->create($paramsBody);
            }
        } catch (Missing404Exception $e) {
            Log::error(sprintf('ElasticSearchBase doCreateOrUpdate missing404 errorMsg %s errorCode %d errorLine %d',
                $e->getMessage(), $e->getCode(), $e->getLine()));
            return false;
        } catch (\Exception $e) {
            Log::error(sprintf('ElasticSearchBase doCreateOrUpdate errorMsg %s errorCode %d errorLine %d',
                $e->getMessage(), $e->getCode(), $e->getLine()));
            return false;
        }
        return true;
    }

    /**
     * @desc 删除一个文档
     * @param $id
     * @return bool
     */
    public function delDoc($id): bool
    {
        Log::info(sprintf('ElasticSearchBase delDoc id=%d', $id));
        $params = [
            'index' => $this->index,
            'id' => $id
        ];
        try {
            $this->esClient->delete($params);
        } catch (Missing404Exception $e) {
            Log::error(sprintf('ElasticSearchBase delDoc missing404 errorMsg %s errorCode %d errorLine %d',
                $e->getMessage(), $e->getCode(), $e->getLine()));
            return false;
        } catch (\Exception $e) {
            Log::error(sprintf('ElasticSearchBase delDoc errorMsg %s errorCode %d errorLine %d',
                $e->getMessage(), $e->getCode(), $e->getLine()));
            return false;
        }
        return true;
    }

    /**
     * @desc 批量添加
     * @param array $data
     * @return array|bool|callable
     */
    public function bulkAdd(array $data)
    {
        if (empty($data)) {
            return false;
        }
        $params = [
            'index' => $this->index,
        ];
        foreach ($data as $message) {
            $params['body'][] = array(
                'create' => array(    #注意create也可换成index
                    '_id' => $message['id']
                ),
            );
            $params['body'][] = $message;
        }
        return $this->esClient->bulk($params);
    }

    /**
     * @desc 获取 ES 的状态信息，包括index 列表
     * @return array
     */
    public function getEsStatus()
    {
        return $this->esClient->indices()->stats();
    }

    /**
     * @desc 获取Index的文档模板信息
     * @return array
     */
    public function getMapping($index = '')
    {
        $params = [
            'index' => $index ? $index : $this->index
        ];
        return $this->esClient->indices()->getMapping($params);
    }

    /**
     * @desc 获取Index的文档模板信息
     * @return array
     */
    public function getSettings($index = '')
    {
        $params = [
            'index' => $index ? $index : $this->index
        ];
        return $this->esClient->indices()->getSettings($params);
    }

    /**
     * @desc
     * @return $this
     */
    private function setBoolQuery()
    {
        if (isset($this->params['body']['query']) && !empty($this->params['body']['query'])) {
            return $this;
        }
        if (isset($this->searchField['bool']) && !empty($this->searchField['bool'])) {
            $this->params['body']['query']['bool'] = $this->searchField['bool'];
        }
        if (isset($this->searchField['suggest']) && !empty($this->searchField['suggest'])) {
            $this->params['body']['suggest'] = $this->searchField['suggest'];
        }
        // 涉及到or查询这里，要组合复合查询条件 (must should同时满足)
        if (isset($this->params['body']['query']['bool']['should']) && isset($this->params['body']['query']['bool']['must'])) {
            $should = $this->params['body']['query']['bool']['should'] ?? [];
            $must = $this->params['body']['query']['bool']['must'] ?? [];
            unset($this->params['body']['query']['bool']['should']);
            unset($this->params['body']['query']['bool']['must']);
            $this->params['body']['query']['bool']['must'][]['bool']['should'] = $should;
            $this->params['body']['query']['bool']['must'][]['bool']['must'] = $must;
        }
        return $this;
    }

    /**
     * @desc 设置执行参数
     * @param array $query
     * @return $this
     */
    public function query(array $query)
    {
        $this->params['body']['query'] = $query;

        return $this;
    }

    /**
     * @desc 检查分词结果
     * @param $keywords
     * @param string $analyzer
     * @return array
     */
    public function checkAnalyzerResult($keywords, string $analyzer = 'ik_max_word')
    {
        if (empty($keywords)) {
            return [];
        }
        $this->params ['body'] = [
            "analyzer" => $analyzer,
            'text' => $keywords
        ];
        return $this->esClient->indices()->analyze($this->params);
    }

    /**
     * @param $data
     * @return array
     */
    protected function getData($data)
    {
        return isset($data["data"]) ? $data["data"] : [];
    }

    /**
     * @param $data
     * @return int
     */
    protected function getTotal($data)
    {
        return isset($data["total"]) ? $data["total"] : 0;
    }
}
