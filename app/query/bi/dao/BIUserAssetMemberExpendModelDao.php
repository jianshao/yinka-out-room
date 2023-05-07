<?php

namespace app\query\bi\dao;

use app\core\mysql\Sharding;
use app\domain\bi\BIConfig;
use app\domain\bi\BIUserAssetModel;
use app\query\user\cache\UserModelCache;


//用户消费模型dao
class BIUserAssetMemberExpendModelDao
{
    protected $serviceName = 'userSlave';
    protected static $instance;

    protected $eventMethod = [];

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new BIUserAssetMemberExpendModelDao();
        }
        return self::$instance;
    }

    public function dataToModel($data)
    {
        $model = new BIUserAssetModel();
        $model->uid = $data['uid'];
        $model->toUid = $data['touid'];
        $model->roomId = $data['room_id'];
        $model->type = $data['type'];
        $model->assetId = $data['asset_id'];
        $model->eventId = $data['event_id'];
        $model->change = $data['change_amount'];
        $model->changeAfter = $data['change_after'];
        $model->changeBefore = $data['change_before'];
        $model->updateTime = $data['success_time'];
        $model->createTime = $data['created_time'];
        $model->ext1 = $data['ext_1'];
        $model->ext2 = $data['ext_2'];
        $model->ext3 = $data['ext_3'];
        $model->ext4 = $data['ext_4'];
        $model->ext5 = $data['ext_5'];
        $model->toNickname = $data['tonickname'] ?: "";
        return $model;
    }

    public function findModelsByWhere($where, $tableName, $shardingColumn, $page, $pagenum = null)
    {
        $ret = [];
        $offset = $page * $pagenum - $pagenum;
        $model = Sharding::getInstance()->getModel($this->serviceName, $tableName, $shardingColumn)->where($where)->order('id desc')->limit($offset, $pagenum)->select();
        if ($model === null) {
            return [0, []];
        }
        $datas = $model->toArray();
        $total = Sharding::getInstance()->getModel($this->serviceName, $tableName, $shardingColumn)->where($where)->count();
        if ($total > 0) {
            foreach ($datas as $data) {
                $data['tonickname'] = UserModelCache::getInstance()->findNicknameByUserId($data['touid']);
                $ret[] = $this->dataToModel($data);
            }
        }
        return [$total, $ret];
    }

    private function initEventMethod()
    {
        $this->eventMethod = [
            BIConfig::$SEND_GIFT_EVENTID => 'getSendGiftExt',
            BIConfig::$BUY_EVENTID => 'getBuyExt',
            BIConfig::$REPLACE_CHARGE_EVENTID => 'getReplaceChangeExt',
            BIConfig::$ACTIVITY_EVENTID => 'getActivityExt',
            BIConfig::$CHARGE_EVENTID => 'getBeanChargeExt',
            BIConfig::$RECEIVE_GIFT_EVENTID => 'getReceiveGiftExt',
            BIConfig::$DIAMOND_EXCHANGE_EVENTID => 'getDiamondExchangeExt',
            BIConfig::$BUY_EVENTID => 'getBuyGoodsExt',
            BIConfig::$TASK_EVENTID => 'getTaskExt',

            99999 => 'defaultEventExt'
        ];
    }

    /**
     * @Info 获取番茄豆的消费流水分页数据模型
     * @param $page
     * @param $pageNum
     * @param $userId
     * @return array
     */
    public function newGetDetailsModels($tableName, $page, $pageNum, $userId, $assetType, $queryStartTime, $queryEndTime, $eventIds = [], $assetId = '')
    {
        $where = [
            ['uid', '=', $userId],
            ['type', '=', $assetType],
            ['success_time', '>=', $queryStartTime],
            ['success_time', '<', $queryEndTime]
        ];
        if ($assetId !== "") {
            $where[] = ['asset_id', '=', $assetId];
        }
        return $this->findModelsByWhere($where, $tableName, $userId, $page, $pageNum);
    }

    /**
     * @Info 获取某个活动的流水分页数据模型
     * @param $page
     * @param $pageNum
     * @param $userId
     * @return array
     */
    public function getActivityDetailsModels($tableName, $page, $pageNum, $userId, $activityType, $queryStartTime, $queryEndTime)
    {
        $where = [
            ['uid', '=', $userId],
            ['ext_1', '=', $activityType],
            ['success_time', '>=', $queryStartTime],
            ['success_time', '<', $queryEndTime]
        ];
        return $this->findModelsByWhere($where, $tableName, $userId, $page, $pageNum);
    }
}















