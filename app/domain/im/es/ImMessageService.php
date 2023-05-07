<?php


namespace app\domain\im\es;

use app\common\EsCommon;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use think\facade\Log;

class ImMessageService
{
    protected $index = 'zb_check_im_message';

    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ImMessageService();
        }
        return self::$instance;
    }

    /**
     * @desc 新增或修改文档
     * @param int $id
     * @param array $data
     * @return bool
     */
    public function doCreateOrUpdate(int $id, array $data): bool
    {
        $params = [
            'index' => $this->index,
            'id' => $id,
        ];

        $paramsBody = $params + [
                'body' => $data
            ];
        try {
            $hasExist = EsCommon::getInstance()->exists($params);
            if ($hasExist) {
                //更新
                $paramsBody['body'] = ['doc' => $data];
                $paramsBody['retry_on_conflict'] = 5;
                EsCommon::getInstance()->update($paramsBody);
            } else {
                EsCommon::getInstance()->create($paramsBody);
            }
        } catch (Missing404Exception $e) {
            Log::error(sprintf('ImMessage doCreateOrUpdate missing404 errorMsg %s errorCode %d errorLine %d',
                $e->getMessage(), $e->getCode(), $e->getLine()));
            return false;
        } catch (\Exception $e) {
            Log::error(sprintf('ImMessage doCreateOrUpdate errorMsg %s errorCode %d errorLine %d',
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
    public function bulk(array $data)
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
        return EsCommon::getInstance()->bulk($params);
    }

}