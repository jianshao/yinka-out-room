<?php
namespace app\domain\game\taojin;

use app\domain\Config;
use app\utils\ArrayUtil;
use think\facade\Log;

class TaojinSystem
{
    protected $taojinMap = [];
    protected $taojins = [];
    protected $energyInfo = [];
    protected $startTime = null;
    protected $endTime = null;

    protected static $instance;
    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new TaojinSystem();
            self::$instance->loadFromJson();
        }
        return self::$instance;
    }

    /**
     * @return TaoJin
     * */
    public function findTaojinByGameId($gameId){
        return ArrayUtil::safeGet($this->taojinMap, intval($gameId));
    }

    public function getEnergyInfo(){
        return $this->energyInfo;
    }

    public function getTaoJinRule(){
        return $this->energyInfo["rule"];
    }

    public function getTaoJinCommonToast(){
        return $this->energyInfo["commontoast"];
    }

    public function getTaoJinLackToast(){
        return $this->energyInfo["lacktoast"];
    }

    public function getTaojins(){
        return $this->taojins;
    }

    public function getGameStatus($timestamp): bool
    {
        return ($timestamp >= $this->startTime) && ($timestamp < $this->endTime);
    }

    public function getGameTime(): array
    {
        return [$this->startTime, $this->endTime];
    }


    protected function loadFromJson() {
        $taojinConfig= Config::getInstance()->getTaoJibConf();
        $taojinMap = [];
        $taojins = [];
        foreach(ArrayUtil::safeGet($taojinConfig, 'games', []) as $conf) {
            if(ArrayUtil::safeGet($conf, 'status', 0) == 0){
                continue;
            }

            $taojin = new TaoJin();
            $taojin->decodeFromJson($conf);
            if (ArrayUtil::safeGet($taojinMap, $taojin->gameId) != null) {
                Log::warning(sprintf('TaojinSystemLoadError gameId=%s err=%s',
                    $taojin->gameId, 'DuplicateGameIdId'));
            } else {
                $taojins[] = $taojin;
                $taojinMap[$taojin->gameId] = $taojin;
            }
        }
        $this->startTime = ArrayUtil::safeGet($taojinConfig,'start_time',0);
        $this->endTime = ArrayUtil::safeGet($taojinConfig,'end_time',0);
        $this->taojins = $taojins;
        $this->taojinMap = $taojinMap;
        $this->energyInfo = ArrayUtil::safeGet($taojinConfig, 'energyInfo', 0);
    }

}

