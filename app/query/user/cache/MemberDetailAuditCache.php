<?php


namespace app\query\user\cache;


use app\common\CacheRedis;
use app\domain\user\model\MemberDetailAuditModel;

class MemberDetailAuditCache
{
    protected $pk = 'id';
    protected static $instance;


    public function __construct(array $data = [])
    {
        $this->redis = CacheRedis::getInstance()->getRedis();
    }

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new MemberDetailAuditCache();
        }
        return self::$instance;
    }

    /**
     * @param $data
     * @return MemberDetailAuditModel
     */
    public function dataToModel($data)
    {
        $ret = new MemberDetailAuditModel();
        $ret->id = $data['id'];
        $ret->userId = $data['user_id'];
        $ret->content = $data['content'];
        $ret->status = $data['status'];
        $ret->action = $data['action'];
        $ret->updateTime = $data['update_time'];
        $ret->createTime = $data['create_time'];
        return $ret;
    }

    public function modelToData(MemberDetailAuditModel $model)
    {
        $ret = [
            'id' => $model->id,
            'user_id' => $model->userId,
            'content' => $model->content,
            'status' => $model->status,
            'action' => $model->action,
            'update_time' => $model->updateTime,
            'create_time' => $model->createTime,
        ];
        return $ret;
    }

    /**
     * @Info member_detail_audit_cache_userId:1454733_action:wall
     * @param $userId
     * @param $action
     * @return string
     */
    private function getCacheKey($userId, $action)
    {
        return sprintf("%s_userId:%s_action:%s", CachePrefix::$memberDetailAuditCache, $userId, $action);
    }


    /**
     * @Info 设置缓存
     * @param int $userId
     * @param string $action
     * @param MemberDetailAuditModel $model
     * @return bool
     */
    public function store(int $userId, string $action, MemberDetailAuditModel $model)
    {
        if (empty($userId) ||empty($action) || is_null($model)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($userId,$action);
        $arrData = MemberDetailAuditCache::getInstance()->modelToData($model);
        $re = $this->redis->hMSet($cacheKey, $arrData);
        $this->redis->expire($cacheKey, 60);
        return $re;
    }


    /**
     * @Info 设置空对象缓存
     * @param int $userId
     * @param string $action
     * @param MemberDetailAuditModel $model
     * @return bool
     */
    public function storeZero(int $userId, string $action, MemberDetailAuditModel $model)
    {
        if (empty($userId) || empty($action) || is_null($model)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($userId,$action);
        $arrData = $this->modelToData($model);
        $re = $this->redis->hMSet($cacheKey, $arrData);
        $this->redis->expire($cacheKey, 10);
        return $re;
    }

//    public function find(int $id)
//    {
//        if (empty($id)) {
//            return false;
//        }
//        $cacheKey = $this->getCacheKey($id);
//        $data = $this->redis->hGetAll($cacheKey);
//        if (empty($data)) {
//            return false;
//        }
//        return $this->dataToModel($data);
//    }

    /**
     * @param $userId
     * @param $action
     * @return MemberDetailAuditModel|false
     */
    public function findMemberDetailByUserId($userId, $action)
    {
        if (empty($userId) || empty($action)) {
            return false;
        }
        $cacheKey = $this->getCacheKey($userId, $action);
        $data = $this->redis->hGetAll($cacheKey);
        if (empty($data)) {
            return false;
        }
        return $this->dataToModel($data);
    }


    public function clearCache(int $userId, string $action)
    {
        $cacheKey = $this->getCacheKey($userId, $action);
        return $this->redis->del($cacheKey);
    }

}