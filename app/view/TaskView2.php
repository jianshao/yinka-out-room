<?php


namespace app\view;


use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetSystem;
use app\domain\asset\AssetUtils;
use app\domain\asset\rewardcontent\RandomContent;
use app\domain\task\Task;
use app\domain\task\TaskKind;
use app\utils\CommonUtil;
use app\utils\StringUtil;

class TaskView2
{
    public static $taskType = [
        'weekCheckIn' => 3,
        'daily' => 2,
        'newer' => 1
    ];

    public static $taskStatus = [
        'weekCheckIn' => 1,
        'daily' => 1,
        'newer' => 1
    ];

    private static function getAssetTypeFromAssetId($assetId) {
        if (AssetUtils::isGiftAsset($assetId)){
            return 'gift';
        }elseif (AssetUtils::isPropAsset($assetId)) {
            return 'prop';
        }
        return substr($assetId, 5);
    }

    public static function encodeReward($taskKind) {
        assert($taskKind instanceof TaskKind);

        $rewardDict = [
            "gift" => [],
            "attire" => [],
            "coin" => 0,
            "gold_coin"=> 0,
            "active_degree" => 0,
        ];
        foreach ($taskKind->reward as $reward){
            $rewardItem = $reward->getContent();
            if (AssetUtils::isGiftAsset($rewardItem->assetId)) {
                $rewardDict['gift'][] = [
                    "gift_id"=>AssetUtils::getGiftKindIdFromAssetId($rewardItem->assetId),
                    "num" => $rewardItem->count
                ];
            }elseif ($rewardItem->assetId == AssetKindIds::$BEAN){
                $rewardDict['coin'] += $rewardItem->count;
            }elseif ($rewardItem->assetId == AssetKindIds::$COIN){
                $rewardDict['gold_coin'] += $rewardItem->count;
            }elseif (StringUtil::startsWith($rewardItem->assetId, 'active_degree')){
                $rewardDict['active_degree'] += $rewardItem->count;
            }
        }
        return $rewardDict;
    }

    // 任务列表展示
    public static function encodeContent($taskKind) {
        assert($taskKind instanceof TaskKind);

        $contentDict = [];
        for ($i = 0; $i < count($taskKind->reward); $i++){
            $rewardItem = $taskKind->reward[$i]->getContent();
            $asset = AssetSystem::getInstance()->findAssetKind($rewardItem->assetId);
            $dict = [
                'type' =>  self::getAssetTypeFromAssetId($asset->kindId),
                'img' =>  CommonUtil::buildImageUrl($asset->image),
                'name' =>  $asset->displayName,
                'num' =>  $rewardItem->count
            ];

            $contentDict[] = $dict;
        }
        return $contentDict;
    }

    // 领取任务奖励展示
    public static function encodeRewardContent($rewardItems) {

        $contentDict = [];
        foreach ($rewardItems as $rewardItem){
            $asset = AssetSystem::getInstance()->findAssetKind($rewardItem->assetId);
            $giftId = AssetUtils::getGiftKindIdFromAssetId($rewardItem->assetId);
            $dict = [
                'giftId' =>  $giftId != null? $giftId:0,
                'gift_image' =>  CommonUtil::buildImageUrl($asset->image),
                'gift_name' =>  $asset->displayName,
                'num' =>  $rewardItem->count
            ];

            $contentDict[] = $dict;
        }
        return $contentDict;
    }

    public static function encodeWeekCheckIn($task, $weekday) {
        assert($task instanceof Task);
        $taskKind = $task->taskKind;
        $isSign = $taskKind->count == $weekday?1:($taskKind->count < $weekday?0:3);
        return [
            'task_id' =>  $taskKind->taskId,
            'task_name' =>  $taskKind->name,
            'task_desc' =>  $taskKind->desc,
            'content' =>  self::encodeContent($taskKind)[0],
		    "is_sign"=>  $task->hasReward()?2:$isSign,
        ];
    }

    public static function encodeDaily($task) {
        assert($task instanceof Task);
        $taskKind = $task->taskKind;
        return [
            'task_id' =>  $taskKind->taskId,
            'task_image' =>  CommonUtil::buildImageUrl($taskKind->image),
            'task_name' =>  $taskKind->name,
            'task_desc' =>  $taskKind->desc,
            'content' =>  self::encodeContent($taskKind),
            "is_finish"=>  $task->hasReward()?2:($task->isFinished()?1:0),
            "toComplete"=> $taskKind->toComplete,
        ];
    }

    public static function encodeNewer($task) {
        assert($task instanceof Task);
        $taskKind = $task->taskKind;
        return [
            'task_id' =>  $taskKind->taskId,
            'task_image' =>  CommonUtil::buildImageUrl($taskKind->image),
            'task_name' =>  strpos($taskKind->name, '(%d/%d)')?sprintf($taskKind->name,$task->progress, $taskKind->count):$taskKind->name,
            'task_desc' =>  $taskKind->desc,
            'content' =>  self::encodeContent($taskKind),
            "is_finish"=>  $task->hasReward()?2:($task->isFinished()?1:0),
            "toComplete"=> $taskKind->toComplete,
        ];
    }

    public static function encodeActiveBox($task) {
        assert($task instanceof Task);
        $taskKind = $task->taskKind;
        $contentDict = [];
        for ($i = 0; $i < count($taskKind->reward); $i++){
            if($taskKind->reward[$i] instanceof RandomContent){
                $rewardItem = $taskKind->reward[$i]->getContent();
                foreach ($rewardItem as $item){
                    $asset = AssetSystem::getInstance()->findAssetKind($item->assetId);
                    $dict = [
                        'giftId' => AssetUtils::getGiftKindIdFromAssetId($item->assetId),
                        'img' => CommonUtil::buildImageUrl($asset->image),
                        'name' =>  $asset->displayName,
                        'num' => $item->count
                    ];

                    $contentDict[] = $dict;
                }
            }
        }

        return $contentDict;
    }
}
