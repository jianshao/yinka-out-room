<?php

namespace app\domain\prop;

use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\prop\dao\UserAttireModelDao;
use app\domain\prop\dao\PropBagModelDao;
use app\domain\prop\dao\PropModelDao;
use app\domain\prop\model\PropBagModel;
use app\domain\prop\model\PropModel;
use app\utils\ArrayUtil;
use think\facade\Log;

class PropBag {
    // 所属userId
    private $user = null;
    // 背包数据
    private $propBagModel = null;
    // 道具map<propId, Prop>
    private $propMap = null;
    // map<kindId, Prop>
    private $kindPropMap = null;
    // 是否加载了
    private $_isLoaded = false;

    public function __construct($user) {
        $this->user = $user;
    }

    /**
     * 是否加载了
     *
     * @return 是否加载了
     */
    public function isLoaded() {
        return $this->_isLoaded;
    }

    /**
     * 加载用户背包
     *
     * @param $timestamp
     */
    public function load($timestamp) {
        if (!$this->isLoaded()) {
            $this->doLoad($timestamp);
            $this->_isLoaded = true;
            $this->processDiedProps($timestamp);
            Log::info(sprintf('PropBagLoad userId=%d count=%d', $this->getUserId(), count($this->propMap)));
        }
    }

    /**
     * 获取背包user对象
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * 获取背包userId
     */
    public function getUserId() {
        return $this->user->getUserId();
    }

    /**
     * 获取用户所有的道具
     *
     * @return map<propId, Prop>
     */
    public function getPropMap() {
        return $this->propMap;
    }

    /**
     * 查找种类为kindId的道具
     * @param kindId 种类ID
     */
    public function findPropByKindId($kindId) {
        return ArrayUtil::safeGet($this->kindPropMap, $kindId);
    }

    /**
     * 根据道具ID查找道具
     */
    public function findPropByPropId($propId) {
        return ArrayUtil::safeGet($this->propMap, $propId);
    }

    /**
     * 处理过期或者数量为0的道具
     */
    public function processDiedProps($timestamp) {
        $propIds = [];
        foreach ($this->propMap as $propId => $prop) {
            if ($prop->isDied($timestamp)) {
                if ($prop->kind->removeFormBagWhenDied) {
                    $propIds[] = $propId;
                } else {
                    $prop->kind->processWhenDied($this, $prop, $timestamp);
                }
            }
        }
        foreach ($propIds as $propId) {
            $this->removeProp($propId);
        }
    }

    /**
     * 删除指定Id的道具
     */
    public function removeProp($propId) {
        $prop = $this->findPropByPropId($propId);
        if ($prop != null) {
            unset($this->propMap[$propId]);
            unset($this->kindPropMap[$prop->kind->kindId]);
            PropModelDao::getInstance()->removeProp($this->getUserId(), $prop->propId);
            Log::info(sprintf('PropBagRemoveProp userId=%d kindId=%d propId=%d',
                    $this->getUserId(), $prop->kind->kindId, $propId));
            // TODO event
        }
    }

    /**
     * 增加count个单位的kindId种类的道具, 有问题抛异常
     */
    public function addPropByUnit($kindId, $count, $timestamp, $biEvent) {
        assert($count > 0);

        // 先查找道具种类是否存在
        $kind = PropSystem::getInstance()->findPropKind($kindId);

        if ($kind == null) {
            throw new FQException('没有该类型的装扮', 500);
        }

        // 找背包里是否存在
        $prop = $this->findPropByKindId($kindId);
        if ($prop == null) {
            $prop = $kind->newProp($this->propBagModel->nextId++);
            $prop->createTime = $timestamp;
            $prop->updateTime = $timestamp;
            $prop->count = 0;
            $prop->expiresTime = 0;
            $prop->isWore = 0;
            $prop->woreTime = 0;
            $prop->add($count, $timestamp);
            $this->kindPropMap[$kindId] = $prop;
            $this->propMap[$prop->propId] = $prop;
            PropBagModelDao::getInstance()->updatePropBag($this->getUserId(), $this->propBagModel);
            PropModelDao::getInstance()->insertProp($this->getUserId(), $this->propToModel($prop));
        } else {
            $prop->add($count, $timestamp);
            PropModelDao::getInstance()->updateProp($this->getUserId(), $this->propToModel($prop));
        }

        $balance = $prop->balance($timestamp);

        BIReport::getInstance()->reportProp($this->getUserId(), $kindId, $count, $balance, $timestamp, $biEvent);

        Log::info(sprintf('PropBagAdd userId=%d kindId=%d propId=%d count=%d balance=%d',
                $this->getUserId(), $prop->kind->kindId, $prop->propId, $count, $balance));

        // TODO event

        return $balance;
    }

    /**
     * 消耗count个单位的kindId种类的道具, 有问题抛异常
     *
     * @return 实际消耗的数量
     */
    public function consumePropByUnit($kindId, $count, $timestamp, $biEvent) {
        assert($count > 0);

        // 先查找道具种类是否存在
        $kind = PropSystem::getInstance()->findPropKind($kindId);

        if ($kind == null) {
            throw new FQException('没有该类型的装扮', 500);
        }

        // 找背包里是否存在
        $prop = $this->findPropByKindId($kindId);
        if ($prop == null) {
            throw new FQException('背包装扮不存在', 500);
        }
        return $this->consume($prop, $count, $timestamp, $biEvent);
    }

    public function consume($prop, $count, $timestamp, $biEvent) {
        $balance = $prop->balance($timestamp);
        if ($balance < $count) {
            throw new FQException('背包数量不足', 500);
        }

        $prop->consume($count, $timestamp);
        $balance = $prop->balance($timestamp);
        PropModelDao::getInstance()->updateProp($this->getUserId(), $this->propToModel($prop));

        BIReport::getInstance()->reportProp($this->getUserId(), $prop->kind->kindId, -$count, $balance, $timestamp, $biEvent);

        Log::info(sprintf('PropBagConsume userId=%d kindId=%d propId=%d balance=%d',
            $this->getUserId(), $prop->kind->kindId, $prop->propId, $balance));

        // TODO event

        if ($prop->isDied($timestamp) && $prop->kind->removeFormBagWhenDied) {
            $this->removeProp($prop->propId);
        }

        return $balance;
    }

    /**
     * 查询kindId类的道具还有多少个单位
     *
     * @return 剩余单位数量
     */
    public function balance($kindId, $timestamp) {
        // 找背包里是否存在
        $prop = $this->findPropByKindId($kindId);
        if ($prop != null) {
            return $prop->balance($timestamp);
        }
        return 0;
    }

    /**
     * 对道具执行动作
     */
    public function doActionByPropId($propId, $action, $actionParams) {
        $prop = $this->findPropByPropId($propId);
        if ($prop == null) {
            throw new FQException('此装扮不存在', 500);
        }
        $timestamp = time();
        $propAction = ArrayUtil::safeGet($prop->kind->actionMap, $action);
        $propAction->doAction($this, $prop, $action, $actionParams, $timestamp);
        return $prop;
    }

    /**
     * 对道具执行动作
     */
    public function doActionByPropKind($kindId, $action, $actionParams) {
        $prop = $this->findPropByKindId($kindId);
        if ($prop == null) {
            throw new FQException('此装扮不存在', 500);
        }
        $timestamp = time();
        $propAction = ArrayUtil::safeGet($prop->kind->actionMap, $action);
        if ($propAction == null) {
            throw new FQException('此装扮不存在该动作', 500);
        }

        list($props, $assetList, $count) = $propAction->doAction($this, $prop, $action, $actionParams, $timestamp);
        return [$prop, $props, $assetList, $count];
    }

    /**
     * 对道具执行动作
     */
    public function doActionByPropType($typeName, $action, $actionParams) {
        $props = [];
        $timestamp = time();
        foreach ($this->propMap as $_propId => $prop) {
            if (!$prop->isDied($timestamp)) {
                $props[] = $prop;
            }
        }
        foreach ($props as $prop) {
            if ($prop->kind->getTypeName() == $typeName) {
                $prop->kind->doAction($this, $prop, $action, $actionParams, $timestamp);
            }
        }
    }

    public function propToModel($prop) {
        $model = new PropModel();
        $model->propId = $prop->propId;
        $model->kindId = $prop->kind->kindId;
        $model->createTime = $prop->createTime;
        $model->updateTime = $prop->updateTime;
        $model->count = $prop->count;
        $model->expiresTime = $prop->expiresTime;
        $model->isWore = $prop->isWore;
        $model->woreTime = $prop->woreTime;
        return $model;
    }

    public function updateProp($prop) {
        PropModelDao::getInstance()->updateProp($this->getUserId(), $this->propToModel($prop));

        Log::info(sprintf('PropBagUpdateProp userId=%d kindId=%d propId=%d isWore=%d',
                    $this->getUserId(), $prop->kind->kindId, $prop->propId, $prop->isWore));

        // TODO event
    }

    private function loadAttires($timestamp) {
        $propModels = [];
        $userAttires = UserAttireModelDao::getInstance()->loadAllByUserId($this->getUserId());
        if (!empty($userAttires)) {
            foreach ($userAttires as $userAttire) {
                $propModel = new PropModel();
                $propModel->propId = $userAttire['id'];
                $propModel->kindId = $userAttire['attid'];
                $propModel->createTime = $userAttire['create_time'];
                $propModel->updateTime = $userAttire['update_time'];
                $propModel->count = 0;
                $propModel->expiresTime = $userAttire['endtime'];
                $propModel->isWore = $userAttire['is_ware'];
                $propModels[] = $propModel;
            }
        }
        return $propModels;
    }

    private function getMaxPropId($propModels) {
        $ret = 0;
        foreach ($propModels as $propModel) {
            if ($propModel->propId > $ret) {
                $ret = $propModel->propId;
            }
        }
        return $ret;
    }

    /**
     * 实际执行加载, 没有则创建
     */
    private function doLoad($timestamp) {
        // 1. 获取背包
        $isCreate = false;
        $propBagModel = PropBagModelDao::getInstance()->loadPropBag($this->getUserId());
        if ($propBagModel == null) {
            // 此时需要转换老的到新的
            $isCreate = true;
            $propModels = $this->loadAttires($timestamp);
            $maxPropId = $this->getMaxPropId($propModels);
            $propBagModel = new PropBagModel($maxPropId + 1, $timestamp, $timestamp);
            PropBagModelDao::getInstance()->createPropBag($this->getUserId(), $propBagModel);
        } else {
            $propModels = PropModelDao::getInstance()->loadAllPropByUserId($this->getUserId());
            $maxPropId = $this->getMaxPropId($propModels);
        }

        $propMap = [];
        $kindPropMap = [];
        foreach ($propModels as $propModel) {
            $propKind = PropSystem::getInstance()->findPropKind($propModel->kindId);
            if ($propKind != null) {
                $prop = $propKind->newProp($propModel->propId);
                $prop->initByPropModel($propModel);
                // 如果已经存在该类型的道具，则删除
                $existsProp = ArrayUtil::safeGet($kindPropMap, $propKind->kindId);
                if ($existsProp != null) {
                    unset($kindPropMap[$existsProp->kind->kindId]);
                    unset($propMap[$existsProp->propId]);
                    Log::warning(sprintf('PropBag::doLoad userId=%d kindId=%d propId=%d err=%d',
                        $this->getUserId(), $propKind->kindId, $prop->propId, 'DuplicateKindId'));
                }
                // 如果已经存在该id的道具，则删除
                $existsProp = ArrayUtil::safeGet($propMap, $prop->propId);
                if ($existsProp != null) {
                    unset($kindPropMap[$existsProp->kind->kindId]);
                    unset($propMap[$existsProp->propId]);
                    Log::warning(sprintf('PropBag::doLoad userId=%d kindId=%d propId=%d err=%d',
                        $this->getUserId(), $propKind->kindId, $prop->propId, 'DuplicateKindId'));
                }
                $propMap[$prop->propId] = $prop;
                $kindPropMap[$prop->kind->kindId] = $prop;
            } else {
                Log::warning(sprintf('NotFoundPropKind %d %d', $this->getUserId(), $propModel->kindId));
            }
        }

        if ($maxPropId >= $propBagModel->nextId) {
            $propBagModel->nextId = $maxPropId + 1;
            $propBagModel->updateTime = $timestamp;
            PropBagModelDao::getInstance()->updatePropBag($this->getUserId(), $propBagModel);
        }

        if ($isCreate) {
            foreach ($propMap as $_ => $prop) {
                $prop->kind->unit->translateOld($prop, $timestamp);
                PropModelDao::getInstance()->insertProp($this->getUserId(), $this->propToModel($prop));
            }
        }

        $this->propBagModel = $propBagModel;
        $this->propMap = $propMap;
        $this->kindPropMap = $kindPropMap;
    }
}


