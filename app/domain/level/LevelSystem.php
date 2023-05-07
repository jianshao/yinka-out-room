<?php


namespace app\domain\level;


use app\domain\Config;
use app\utils\ArrayUtil;
use think\facade\Log;

class LevelSystem
{
    private $levelMap = [];
    private $levels = [];
    // 特权等级
    private $privilegeLevelMap = [];
    private $privilegeLevels = [];

    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new LevelSystem();
            self::$instance->loadFromJson();
        }
        return self::$instance;
    }

    /**
     * @return [PrivilegeLevel];
     */
    public function getPrivilegeLevels()
    {
        return $this->privilegeLevels;
    }

    /**
     * 获取$oldLevel-$newLevel之间的特权奖励
     * @return [PrivilegeLevel];
     */
    public function getPrivilegesByLevel($oldLevel, $newLevel)
    {
        $privileges = [];
        foreach ($this->privilegeLevelMap as $level => $privilege) {
            if ($level > $oldLevel and $level <= $newLevel) {
                $privileges[] = $privilege;
            }
        }
        return $privileges;
    }

    /**
     * 获取该经验值的等级,初始等级默认为1
     * @return int;
     */
    public function getLevelByExp($exp)
    {
        $level = 1;
        for ($i = 0; $i < count($this->levels); $i++) {
            if ($exp >= $this->levels[$i]->count) {
                $level = $this->levels[$i]->level;
            }
        }
        return $level;
    }

    /**
     * @return Level;
     */
    public function getLevelByLevel($level)
    {
        return ArrayUtil::safeGet($this->levelMap, $level);
    }


    /**
     * 该经验值是否可以升级，如果可以返回升级的level
     * $nowLevel 现在的等级
     * $exp 总经验值
     * @return int;
     */
    public function canUpgrade($nowLevel, $totalExp)
    {
        $level = $this->getLevelByExp($totalExp);

        if ($level > $nowLevel)
            return $level;

        return null;
    }

    protected function loadFromJson()
    {
//        if(config("config.appDev")=="dev"){
        $levelCong = Config::getInstance()->getLevelConf();
//        }else{
//            $levelCong = Config::getInstance()->getLevelConf();
//        }
        $levelMap = [];
        $levels = [];
        foreach (ArrayUtil::safeGet($levelCong, 'level', []) as $conf) {
            $level = new Level();
            $level->decodeFromJson($conf);
            if (ArrayUtil::safeGet($levelMap, $level->level) != null) {
                Log::warning(sprintf('LevelSystemErrro level=%s err=%s',
                    $level->level, 'DuplicateLevel'));
            } else {
                $levels[] = $level;
                $levelMap[$level->level] = $level;
            }
        }
        $this->levels = $levels;
        $this->levelMap = $levelMap;

        $privilegeLevelMap = [];
        $privilegeLevels = [];
        foreach (ArrayUtil::safeGet($levelCong, 'privilege', []) as $conf) {
            $level = new PrivilegeLevel();
            $level->decodeFromJson($conf);
            if (ArrayUtil::safeGet($privilegeLevelMap, $level->level) != null) {
                Log::warning(sprintf('LevelSystemErrro privilegelevel=%s err=%s',
                    $level->level, 'DuplicatePrivilegeLevel'));
            } else {
                $privilegeLevels[] = $level;
                $privilegeLevelMap[$level->level] = $level;
            }
        }
        $this->privilegeLevels = $privilegeLevels;
        $this->privilegeLevelMap = $privilegeLevelMap;
    }
}