<?php
/**
 * User: yond
 * Date: 2020
 * 资产变化表
 */
namespace app\domain\bi;
use app\core\mysql\ModelDao;
use app\core\mysql\Sharding;
use think\facade\Db;

class BIUserAssetModelDao extends ModelDao {

    protected $table = 'zb_user_asset_log';
    protected $pk = 'id';
    protected static $instance;
    public $currMonthTable = 'zb_user_asset_log_%s';
    protected $serviceName = 'userMaster';

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new BIUserAssetModelDao();
        }
        return self::$instance;
    }

    /**
     * @param $model BIUserAssetModel
     */
    public function addData($model) {
        $data = [
            'uid' => $model->uid,
            'touid' => $model->toUid,
            'room_id' => $model->roomId,
            'type' => $model->type,
            'asset_id' => $model->assetId,
            'event_id' => $model->eventId,
            'change_amount' => $model->change,
            'change_after' => $model->changeAfter,
            'change_before' => $model->changeBefore,
            'success_time' => $model->updateTime,
            'created_time' => $model->createTime,
            'ext_1' => $model->ext1,
            'ext_2' => $model->ext2,
            'ext_3' => $model->ext3,
            'ext_4' => $model->ext4,
            'ext_5' => $model->ext5,
        ];
        //user_asset_log 总计3个月数据
        $this->getModel($model->uid)->insert($data);
        //user_asset_log_%s 当前月份数据
        $dnName = $this->getDbName($model->uid);
        Db::connect($dnName)->table(sprintf($this->currMonthTable, date('Ym', $model->createTime)))->insert($data);
    }
}