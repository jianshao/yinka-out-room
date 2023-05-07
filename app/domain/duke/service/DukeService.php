<?php


namespace app\domain\duke\service;

use app\core\mysql\Sharding;
use app\domain\duke\DukeSystem;
use app\domain\duke\model\DukeModel;
use app\domain\exceptions\FQException;
use app\domain\user\UserRepository;
use app\event\DukeLevelChangeEvent;
use think\facade\Log;

class DukeService
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new DukeService();
        }
        return self::$instance;
    }

    public function processDukeWhenUserLogin($userId, $lastLoginTime, $timestamp) {
        $timestamp = time();

        list($oldDukeModel, $newDukeModel) = $this->adjustDuke($userId, $timestamp);

        if ($oldDukeModel->dukeLevel != $newDukeModel->dukeLevel) {
            Log::info(sprintf('DukeService::processDuke LevelChanged userId=%d oldDukeLevel=%d newDukeLevel=%d dukeValue=%d dukeExpires=%d',
                $userId, $oldDukeModel->dukeLevel, $newDukeModel->dukeLevel,
                $newDukeModel->dukeValue, $newDukeModel->dukeExpiresTime));
            event(new DukeLevelChangeEvent($userId, $oldDukeModel->dukeLevel, $newDukeModel->dukeLevel, 0, $timestamp));
        }
    }

    public function addDukeValue($userId, $value, $roomId=0) {
        if ($value > 0) {
            $timestamp = time();

            list($oldDukeModel, $newDukeModel) = $this->adjustDuke($userId, $timestamp, $value);

            Log::info(sprintf('DukeService::addDukeValue userId=%d value=%d oldDukeLevel=%d newDukeLevel=%d dukeValue=%d dukeExpires=%d roomId=%d',
                $userId, $value, $oldDukeModel->dukeLevel, $newDukeModel->dukeLevel,
                $newDukeModel->dukeValue, $newDukeModel->dukeExpiresTime, $roomId));

            if ($oldDukeModel->dukeLevel != $newDukeModel->dukeLevel) {
                Log::info(sprintf('DukeService::addDukeValue LevelChanged userId=%d oldDukeLevel=%d newDukeLevel=%d dukeValue=%d dukeExpires=%d roomId=%d',
                    $userId, $oldDukeModel->dukeLevel, $newDukeModel->dukeLevel,
                    $newDukeModel->dukeValue, $newDukeModel->dukeExpiresTime,
                    $roomId));
                event(new DukeLevelChangeEvent($userId, $oldDukeModel->dukeLevel, $newDukeModel->dukeLevel, $roomId, $timestamp));
            }
        }
    }

    private function adjustDuke($userId, $timestamp, $addValue=0) {
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $timestamp, $addValue) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }
                $duke = $user->getDuke($timestamp);
                $oldDukeModel = $duke->getModel()->copyTo(new DukeModel());
                $duke->adjust($timestamp, $addValue);
                $newDukeModel = $duke->getModel();
                return [$oldDukeModel, $newDukeModel];
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $userId
     * @param $dukeId
     * @param $dukeExpires
     * @param $timestamp
     * @return mixed
     */
    public function dukeChangeForLevelId($userId, $dukeId, $dukeExpires, $timestamp)
    {
        return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use ($userId, $dukeId, $dukeExpires, $timestamp) {
            $user = UserRepository::getInstance()->loadUser($userId);
            if ($user == null) {
                throw new FQException('用户不存在', 500);
            }
            $duke = $user->getDuke($timestamp);
            $oldLevel = $duke->getModel()->dukeLevel;
            $dukeLevelModel = DukeSystem::getInstance()->findDukeLevel($dukeId);
            if ($dukeLevelModel === null) {
                throw new FQException("dukelevelModel error", 500);
            }
            $oldDukeModel = $duke->getModel()->copyTo(new DukeModel());
            $duke->setDukeModelForLevel($dukeLevelModel, $dukeExpires);
            $duke->dukeUpdate($timestamp, $oldLevel);
            $newDukeModel = $duke->getModel();

            if ($oldDukeModel->dukeLevel != $newDukeModel->dukeLevel) {
                Log::info(sprintf('DukeService::dukeChangeForLevelId LevelChanged userId=%d oldDukeLevel=%d newDukeLevel=%d dukeValue=%d dukeExpires=%d',
                    $userId, $oldDukeModel->dukeLevel, $newDukeModel->dukeLevel,
                    $newDukeModel->dukeValue, $newDukeModel->dukeExpiresTime));
                event(new DukeLevelChangeEvent($userId, $oldDukeModel->dukeLevel, $newDukeModel->dukeLevel, 0, $timestamp));
            }
            return true;
        });
    }
}