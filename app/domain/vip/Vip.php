<?php


namespace app\domain\vip;


use app\domain\bi\BIReport;
use app\domain\exceptions\FQException;
use app\domain\vip\dao\VipModelDao;
use app\domain\vip\service\VipService;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\Exception;
use think\facade\Log;

class Vip
{
    private $user = null;
    private $model = null;
    private $_isLoaded = false;

    public function  __construct($user) {
        $this->user = $user;
    }

    /**
     * 是否加载了
     */
    public function isLoaded() {
        return $this->_isLoaded;
    }

    /**
     * 加载用户背包
     */
    public function load($timestamp) {
        if (!$this->isLoaded()) {
            $this->doLoad($timestamp);
            $this->_isLoaded = true;
            Log::info(sprintf('VipLoaded userId=%d vipLevel=%d vipExpiresTime=%d svipExpiresTime=%d',
                $this->getUserId(), $this->model->level,
                $this->model->vipExpiresTime, $this->model->svipExpiresTime));
        }
    }

    public function getUserId() {
        return $this->user->getUserId();
    }

    public function getModel() {
        return $this->model;
    }

    public function getVipLevel() {
        return $this->model->level;
    }

    public function getVipExpiresTime() {
        return $this->model->vipExpiresTime;
    }

    public function getSvipExpiresTime() {
        return $this->model->svipExpiresTime;
    }

    public function vipBalance($timestamp) {
        if ($this->model->level < 1) {
            return 0;
        }
        if ($this->model->vipExpiresTime <= $timestamp) {
            return 0;
        }
        return ceil(($this->model->vipExpiresTime - $timestamp) / 86400.0);
    }

    public function svipBalance($timestamp) {
        if ($this->model->level < 2) {
            return 0;
        }
        if ($this->model->svipExpiresTime <= $timestamp) {
            return 0;
        }
        return ceil(($this->model->svipExpiresTime - $timestamp) / 86400.0);
    }

    public function addVip($count, $timestamp, $biEvent = []) {
        assert($count >= 0);
        if ($this->model->level < 1) {
            $this->model->level = 1;
            $this->model->vipExpiresTime = 0;
        }
        if ($this->model->vipExpiresTime < $timestamp) {
            $this->model->vipExpiresTime = $timestamp;
        }
        // 涉及到vip一天体验卡，不能直接从当天零点计算
        if (ArrayUtil::safeGet($biEvent,'ext5') !== true){
            $this->model->vipExpiresTime = TimeUtil::calcDayStartTimestamp($this->model->vipExpiresTime);
        }
        $this->model->vipExpiresTime += 86400 * $count;

        $this->processVip($timestamp, true);
    }

    public function addSvip($count, $timestamp, $biEvent = []) {
        assert($count >= 0);
        if ($this->model->level < 2) {
            $this->model->level = 2;
            $this->model->svipExpiresTime = 0;
        }

        if ($this->model->svipExpiresTime < $timestamp) {
            $this->model->svipExpiresTime = $timestamp;
        }

        // 涉及到vip一天体验卡，不能直接从当天零点计算
        if (ArrayUtil::safeGet($biEvent,'ext5') !== true){
            $this->model->svipExpiresTime = TimeUtil::calcDayStartTimestamp($this->model->svipExpiresTime);
        }

        $this->model->svipExpiresTime += 86400 * $count;

        $this->processVip($timestamp, true);
    }

    public function processVip($timestamp, $changed=false) {
        $oldLevel = $this->model->level;
        if ($this->model->level > 1) {
            if ($timestamp >= $this->model->svipExpiresTime) {
                if ($timestamp >= $this->model->vipExpiresTime) {
                    $this->model->level = 0;
                } else {
                    $this->model->level = 1;
                }
                $changed = true;
            }
        } elseif ($this->model->level == 1) {
            if ($timestamp >= $this->model->vipExpiresTime) {
                $changed = true;
                $this->model->level = 0;
            }
        }

        if ($changed) {
            VipModelDao::getInstance()->saveVip($this->getUserId(), $this->model);
            $this->processVipAssets($oldLevel, $timestamp);
            // 记录最新的过期时间
            VipService::getInstance()->recordVipExpire($this->getUserId(), $timestamp);
        }
    }

    private function doLoad($timestamp) {
        $model = VipModelDao::getInstance()->loadVip($this->getUserId());
        if ($model == null) {
            throw new FQException('用户Vip数据不存在', 500);
        }
        $this->model = $model;
    }

    private function addOrConsumeVipAssets($userAssets, $vipLevel, $timestamp, $biEvent, $consume) {
        foreach ($vipLevel->privilegeAssets as $privilegeAsset) {
            try {
                if ($consume) {
                    $balance = $userAssets->balance($privilegeAsset->assetId, $timestamp);
                    if ($balance > 0) {
                        $userAssets->consume($privilegeAsset->assetId, $balance, $timestamp, $biEvent);
                    }
                } else {
                    $userAssets->add($privilegeAsset->assetId, $privilegeAsset->count, $timestamp, $biEvent);
                }
            } catch (FQException | Exception $e) {
                Log::error(sprintf('Vip::addOrConsumeVipAssets userId=%d assetId=%s ex=%d:%s',
                    $this->getUserId(), $privilegeAsset->assetId, $e->getCode(), $e->getMessage()));
            }
        }
    }

    private function processVipAssets($oldLevel, $timestamp) {
        // 根据当前特权，把不等于当前特权的资产全部删除，并增加当前爵位的特权资产
        // 删除不属于当前级别的特权资产
        $userAssets = $this->user->getAssets();

        $vipLevel1 = VipSystem::getInstance()->findVipLevel(1);
        $vipLevel2 = VipSystem::getInstance()->findVipLevel(2);

        $nowLevel = $this->getVipLevel();
        $biEvent = BIReport::getInstance()->makeVipBIEvent($oldLevel, $nowLevel);

        if ($nowLevel == 2) {
            // 增加vip2的特权
            if ($vipLevel2) {
                $this->addOrConsumeVipAssets($userAssets, $vipLevel2, $timestamp, $biEvent, false);
            }
        } elseif ($this->getVipLevel() == 1) {
            // 去掉vip2的特权
            if ($vipLevel2) {
                $this->addOrConsumeVipAssets($userAssets, $vipLevel2, $timestamp, $biEvent, true);
            }
            // 增加vip1的特权
            if ($vipLevel1) {
                $this->addOrConsumeVipAssets($userAssets, $vipLevel1, $timestamp, $biEvent, false);
            }
        } else {
            // 去掉vip2的特权
            if ($vipLevel2) {
                $this->addOrConsumeVipAssets($userAssets, $vipLevel2, $timestamp, $biEvent, true);
            }
            // 去掉vip1的特权
            if ($vipLevel1) {
                $this->addOrConsumeVipAssets($userAssets, $vipLevel1, $timestamp, $biEvent, true);
            }
        }
    }
}