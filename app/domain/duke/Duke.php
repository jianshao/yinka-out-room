<?php


namespace app\domain\duke;


use app\domain\bi\BIReport;
use app\domain\duke\dao\DukeLogModelDao;
use app\domain\duke\dao\DukeModelDao;
use app\domain\exceptions\FQException;
use think\facade\Log;

class Duke
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

    public function getUser() {
        return $this->user;
    }

    public function getModel() {
        return $this->model;
    }

    public function adjust($timestamp, $addValue = 0)
    {
        $oldLevel = $this->model->dukeLevel;
        $changed = DukeSystem::getInstance()->adjustDuke($this->model, $timestamp);
        if ($addValue > 0) {
            $this->model->dukeValue += $addValue;
            $changed = true;
            DukeSystem::getInstance()->adjustDuke($this->model, $timestamp);
        }
        if ($changed) {
            return $this->dukeUpdate($timestamp, $oldLevel);
        }
        return false;
    }

    /**
     * @Info 爵位调整
     * @param $timestamp
     * @param $oldLevel
     * @return bool
     */
    public function dukeUpdate($timestamp, $oldLevel)
    {
        DukeModelDao::getInstance()->saveDuke($this->getUserId(), $this->model);
        if ($oldLevel != $this->model->dukeLevel) {
            DukeLogModelDao::getInstance()->addDukeLog($this->getUserId(), $this->model->dukeLevel, $timestamp);
            $this->processDukeAssets($oldLevel, $timestamp);
        }
        return true;
    }

    public function setDukeModelForLevel(DukeLevel $dukeLevelModel, $dukeExpires)
    {
        $this->model->dukeLevel = $dukeLevelModel->level;
        $this->model->dukeValue = $dukeLevelModel->value;
        $this->model->dukeExpiresTime = $dukeExpires;
    }

    /**
     * 加载用户背包
     */
    public function load($timestamp)
    {
        if (!$this->isLoaded()) {
            $this->doLoad($timestamp);
            $this->_isLoaded = true;
            Log::info(sprintf('DukeLoaded userId=%d level=%d expiresTime=%d',
                $this->getUserId(), $this->model->dukeLevel, $this->model->dukeExpiresTime));
        }
    }

    public function getUserId()
    {
        return $this->user->getUserId();
    }

    private function doLoad($timestamp)
    {
        $model = DukeModelDao::getInstance()->loadDuke($this->getUserId());
        if ($model == null) {
            throw new FQException('用户爵位数据不存在', 500);
        }
        $this->model = $model;
    }

    private function processDukeAssets($oldLevel, $timestamp)
    {
        // 根据当前特权，把不等于当前特权的资产全部删除，并增加当前爵位的特权资产
        // 删除不属于当前级别的特权资产
        $userAssets = $this->user->getAssets();
        foreach (DukeSystem::getInstance()->getDukeLevelList() as $dukeLevel) {
            if ($this->model->dukeLevel != $dukeLevel->level) {
                $consume = true;
            } else {
                $consume = false;
            }
            $biEvent = BIReport::getInstance()->makeDukeBIEvent($oldLevel, $this->model->dukeLevel);
            foreach ($dukeLevel->privilegeAssets as $privilegeAsset) {
                try {
                    if ($consume) {
                        $balance = $userAssets->balance($privilegeAsset->assetId, $timestamp);
                        if ($balance > 0) {
                            $userAssets->consume($privilegeAsset->assetId, $balance, $timestamp, $biEvent);
                        }
                    } else {
                        $userAssets->add($privilegeAsset->assetId, $privilegeAsset->count, $timestamp, $biEvent);
                    }
                } catch (FQException $e) {
                    Log::error(sprintf('Duke::processDukeAssets userId=%d assetId=%s ex=%d:%s',
                        $this->getUserId(), $privilegeAsset->assetId, $e->getCode(), $e->getMessage()));
                }
            }
        }
    }
}