<?php


namespace app\domain\activity\duobao3;


use app\common\RedisCommon;
use app\utils\ArrayUtil;

class Config
{
    # 桌子配置
    protected $tableConfigMap = [];
    protected $duobaoExpiresSeconds = 3600;
    protected $perUserMacGrabCount = 2;
    protected $tableId2TableNameMap = [
        1 => 'min',
        2 => 'mid',
        3 => 'max'
    ];
    protected $TableConfigKey = 'three_loot_config';

    protected static $instance;
    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new Config();
            self::$instance->_doLoad();
        }
        return self::$instance;
    }

    public function getDuobaoExpiresSeconds() {
        return $this->duobaoExpiresSeconds;
    }

    public function getPerUserMaxGrabCount() {
        return $this->perUserMacGrabCount;
    }

    public function getTableConfigMap() {
        return $this->tableConfigMap;
    }

    public function getTableConfig($tableId) {
        return ArrayUtil::safeGet($this->tableConfigMap, $tableId);
    }

    /**
     * 根据tableId获取tableName
     *
     * @param $tableId
     * @return mixed|null
     */
    public function getTableName($tableId) {
        return ArrayUtil::safeGet($this->tableId2TableNameMap, $tableId, '');
    }

    protected function decodeTableConfig($data) {
        $tableId = $data['type'];
        $tableName = $this->getTableName($tableId);
        if (empty($tableName)) {
            return null;
        }
        $ret = new TableConfig();
        $ret->tableId = $tableId;
        $ret->tableName = $tableName;
        $data['pool_info'] = json_decode($data['pool_info']);
        foreach ($data['pool_info'] as $giftConf) {
            $giftConf = (array)$giftConf;
            $giftId = intval($giftConf['gift_id']);
            $price = intval($giftConf['pay_price']);
            $giftName = $giftConf['gift_name'];
            $giftCoin = intval($giftConf['gift_coin']);
            $giftImage = $giftConf['gift_image'];
            $tableGift = new TableGift();
            $tableGift->giftId = $giftId;
            $tableGift->price = $price;
            $tableGift->giftName = $giftName;
            $tableGift->giftCoin = $giftCoin;
            $tableGift->giftImage = $giftImage;
            $ret->tableGifts[] = $tableGift;
        }
        return $ret;
    }

    protected function _doLoad() {
        $redis = RedisCommon::getInstance()->getRedis();
        $datas = $redis->get($this->TableConfigKey);
        if (empty($dats)) {
            $datas = ConfigDao::getInstance()->getPools();
            //写入缓存
            $redisData = [];
            foreach ($datas as $data) {
                $redisData[] = $data;
            }
            $redis->set($this->TableConfigKey, json_encode($redisData));
        } else {
            $datas = json_decode($datas, true);
        }
        $tableConfigMap = [];
        if (!empty($datas)) {
            foreach ($datas as $data) {
                $tableConfig = $this->decodeTableConfig($data);
                if ($tableConfig != null && count($tableConfig->tableGifts) > 0) {
                    $tableConfigMap[$tableConfig->tableId] = $tableConfig;
                }
            }
        }
        $this->tableConfigMap = $tableConfigMap;
    }
}