<?php


namespace app\domain\activity\acrosspk;

use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\room\dao\RoomModelDao;
use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;
use Exception;

class AcrossPKService
{
    protected static $instance;
    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new AcrossPKService();
        }
        return self::$instance;
    }

    public static $rankMap = [16, 8, 4, 2, 1];
    public static $acrossKey = 'across_pk_activity';

    public function matchKey($rank){
        return $rank."_rank";
    }

    public function setAcrossPKRank($rank, $roomIds){
        if ($this->isExpire()){
            return;
        }

        Log::info(sprintf('AcrossPKService.setAcrossPKRank rank=%d roomIds=%s', $rank, json_encode($roomIds)));
        if (!in_array($rank, self::$rankMap)){
            throw new FQException(sprintf('没有该%s强', $rank),500);
        }

        $roomIds = array_unique($roomIds);
        if (count($roomIds) != $rank){
            throw new FQException('roomIds的长度跟rank不匹配',500);
        }

        $roomMap = RoomModelDao::getInstance()->findRoomModelsMap($roomIds);
        if (count($roomMap) != $rank){
            $noExsitRoomIds = [];
            foreach ($roomIds as $key=>$roomId){
                if (!ArrayUtil::safeGet($roomMap, $roomId)){
                    $noExsitRoomIds[] = $roomId;
                }
            }
            throw new FQException('没有该roomId:'.json_encode($noExsitRoomIds),500);
        }

//        shuffle($roomIds);

        $redis = RedisCommon::getInstance()->getRedis();
        $redis->hSet(self::$acrossKey, $rank, json_encode($roomIds));

        if ($rank != 1){
            # 分配房间
            $matchRooms = array_chunk($roomIds, 2);

            $matchInfo = [];
            for ($i=0; $i<count($matchRooms); $i++){
                $matchInfo[] = [
                    'createRoomId' => $matchRooms[$i][0],
                    'pkRoomId' => $matchRooms[$i][1],
                    'winRoomId' => 0
                ];
            }
            $redis->hSet(self::$acrossKey, $this->matchKey($rank), json_encode($matchInfo));
        }

        foreach (self::$rankMap as $key => $value){
            if ($value == $rank){
                $index = $key;
                break;
            }
        }

        if ($index > 0){
            # 更新上一局谁赢谁输
            $needUpdateRank = self::$rankMap[$index-1];
            $matchInfo = $redis->hGet(self::$acrossKey, $this->matchKey($needUpdateRank));
            if (!empty($matchInfo)) {
                $matchInfo = json_decode($matchInfo, true);
                foreach ($matchInfo as $key=>&$info){
                    if (in_array($info['createRoomId'], $roomIds)){
                        $info['winRoomId'] = $info['createRoomId'];
                    }elseif (in_array($info['pkRoomId'], $roomIds)){
                        $info['winRoomId'] = $info['pkRoomId'];
                    }
                }
                $redis->hSet(self::$acrossKey, $this->matchKey($needUpdateRank), json_encode($matchInfo));
            }
            Log::info(sprintf('AcrossPKService.setAcrossPKRank updateRank index=%d matchInfo=%s', $index, json_encode($matchInfo)));
        }
    }

    public function isExpire(){
        $redis = RedisCommon::getInstance()->getRedis();
        $timestamp = time();
        $startTime = $redis->hGet(self::$acrossKey, 'start_time');
        $stopTime = $redis->hGet(self::$acrossKey, 'stop_time');
        $startTime = TimeUtil::strToTime($startTime);
        $stopTime = TimeUtil::strToTime($stopTime);
        if ($timestamp < $startTime || $timestamp > $stopTime){
            return true;
        }

        return false;
    }

}