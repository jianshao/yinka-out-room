<?php

namespace app\domain\prop;

use app\domain\Config;
use app\domain\prop\model\PropBagModel;
use app\utils\ArrayUtil;
use think\facade\Log;

/**
 * 道具种类系统
 */
class PropSystem
{
    protected static $instance;
    # 类型map
    private $kindMap = [];

    // 单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new PropSystem();
            self::$instance->loadFromJson();
            Log::info(sprintf('PropSystemLoaded count=%d', count(self::$instance->kindMap)));
        }
        return self::$instance;
    }

    /**
     * 根据kindId查找
     * 
     * @param kindId: 类型ID
     * 
     * @return: 找到返回PropKind, 没找到返回null
     */

    /**
     * @param $kindId
     * @return mixed|null
     */
    public function findPropKind($kindId) {
        return ArrayUtil::safeGet($this->kindMap, $kindId);
    }

    /**
     * 获取所有道具类型
     * 
     * @return: map<kindId, PropKind>
     */
    public function getKindMap() {
        return $this->kindMap;
    }

    private function loadFromJson() {
        $jsonObj = Config::getInstance()->getPropConf();
        $kindMap = [];
        foreach ($jsonObj['props'] as $kindConf) {
            $kind = PropKindRegister::getInstance()->decodeFromJson($kindConf);
            $kindMap[$kind->kindId] = $kind;
        }
        $this->kindMap = $kindMap;
    }
}


PropUnitRegister::getInstance()->register(PropUnitCount::$TYPE_NAME, PropUnitCount::class);
PropUnitRegister::getInstance()->register(PropUnitLife::$TYPE_NAME, PropUnitLife::class);
PropUnitRegister::getInstance()->register(PropUnitDay::$TYPE_NAME, PropUnitDay::class);
PropUnitRegister::getInstance()->register(PropUnitWearDay::$TYPE_NAME, PropUnitWearDay::class);
PropUnitRegister::getInstance()->register(PropUnitCountMaxN::$TYPE_NAME, PropUnitCountMaxN::class);
PropUnitRegister::getInstance()->register(PropUnitCountMax1::$TYPE_NAME, PropUnitCountMax1::class);

PropKindRegister::getInstance()->register(PropKindAvatar::$TYPE_NAME, PropKindAvatar::class);
PropKindRegister::getInstance()->register(PropKindBubble::$TYPE_NAME, PropKindBubble::class);
PropKindRegister::getInstance()->register(PropKindMount::$TYPE_NAME, PropKindMount::class);
PropKindRegister::getInstance()->register(PropKindVoiceprint::$TYPE_NAME, PropKindVoiceprint::class);
PropKindRegister::getInstance()->register(PropKindSimple::$TYPE_NAME, PropKindSimple::class);
PropKindRegister::getInstance()->register(PropKindCard::$TYPE_NAME, PropKindCard::class);
PropKindRegister::getInstance()->register(PropKindImBubble::$TYPE_NAME, PropKindImBubble::class);

PropActionRegister::getInstance()->register(PropActionUse::$TYPE_NAME, PropActionUse::class);
PropActionRegister::getInstance()->register(PropActionWear::$TYPE_NAME, PropActionWear::class);
PropActionRegister::getInstance()->register(PropActionUnWear::$TYPE_NAME, PropActionUnWear::class);
PropActionRegister::getInstance()->register(PropActionBreakup::$TYPE_NAME, PropActionBreakup::class);


