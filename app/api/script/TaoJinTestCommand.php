<?php
/**
 * 定时任务
 * 语音轮询
 * 语音异步检测
 */

namespace app\api\script;

use app\common\RedisCommon;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetSystem;
use app\domain\game\taojin\TaojinService;
use app\domain\game\taojin\TaojinSystem;
use app\utils\CommonUtil;
use think\console\Command;
use think\console\Input;
use think\console\Output;

ini_set('set_time_limit', 0);

class TaoJinTestCommand extends Command
{
    protected function configure()
    {
        $this->setName('TaoJinTestCommand')->setDescription('TaoJinTestCommand');
    }

    public function decodeTask($taskData)
    {
        try {
            $task = json_decode($taskData, true);
            $gameId = intval($task['gameId']);
            $loopCount = intval($task['loopCount']);
            $num = intval($task['num']);
            $taskId = $task['taskId'];
            $userId = $task['userId'];
            $count = $task['count'];

            return $task;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function setTaskStatus($task, $status)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $resKey = 'taojin_task_res';
        $taskData = [
            'task' => $task,
            'status' => $status,
        ];
        $redis->hSet($resKey, $task['taskId'], json_encode($taskData));
        echo 'set task status' . $task['taskId'] . ':' . json_encode($taskData);
    }

    //语音轮询
    protected function execute(Input $input, Output $output)
    {
        $subKey = 'taojin_task_sub';
        $redis = RedisCommon::getInstance()->getRedis();
        while (true) {
            $taskData = $redis->lPop($subKey);
            if ($taskData !== false) {
                echo 'received task', $taskData;
                $task = $this->decodeTask($taskData);
                if ($task) {
                    $this->setTaskStatus($task, [
                        'status' => 0,
                        'progress' => 0,
                    ]);
                    list($status, $progress) = $this->runTask($task);
                    $this->setTaskStatus($task, [
                        'status' => $status,
                        'progress' => $progress,
                    ]);
                }
            }
            usleep(10);
        }
    }

    public function runTask($task)
    {
        $orePrimary = [
            AssetKindIds::$BEAN => 1,
            AssetKindIds::$TAOJIN_ORE_IRON => 222,
            AssetKindIds::$TAOJIN_ORE_SILVER => 1733,
            AssetKindIds::$TAOJIN_ORE_GOLD => 4380,
            AssetKindIds::$TAOJIN_ORE_FOSSIL => 11147,
        ];

        echo "count:" . $task['count'] . PHP_EOL;

        $string = '';
        for ($j = 1; $j <= $task['count']; $j++) {
            $task_string = $this->getStr($task);
            $string .= $task_string;
        }

        $filename = '/www/wwwroot/mua/public/static/' . $task['taskId'] . '.csv';
        $saveFileRes = $this->saveFile($filename, $string);
        echo 'saveFile ret=' . $saveFileRes . PHP_EOL;
        return [2, 100];
    }

    public function getStr($task)
    {
        $orePrimary = [
            AssetKindIds::$BEAN => 1,
            AssetKindIds::$TAOJIN_ORE_IRON => 222,
            AssetKindIds::$TAOJIN_ORE_SILVER => 1733,
            AssetKindIds::$TAOJIN_ORE_GOLD => 4380,
            AssetKindIds::$TAOJIN_ORE_FOSSIL => 11147,
        ];

        $taojinGame = TaojinSystem::getInstance()->findTaojinByGameId($task['gameId']);
        if ($taojinGame == null) {
            // 任务设置为失败
            return [3, 0];
        }
        $rewards = [];

        $string = '';
        $res = [];
        for ($i = 1; $i <= $task['loopCount']; $i++) {
            $taojinRewards = TaojinService::getInstance()->rollDice($task['userId'], $task['num'], $taojinGame);
            foreach ($taojinRewards as list($diceNum, $taojinReward)) {
                $asset = AssetSystem::getInstance()->findAssetKind($taojinReward->reward->assetId);
                if ($asset != null) {
                    $reward = [
                        'step' => $diceNum,
                        'giftid' => $taojinReward->reward->assetId,
                        'type' => $taojinReward->reward->assetId == AssetKindIds::$BEAN ? 5 : 0,
                        'gift_coin' => $orePrimary[$taojinReward->reward->assetId],
                        'giftnum' => $taojinReward->reward->count,
                        'gift_name' => $asset->displayName,
                        'gift_image' => CommonUtil::buildImageUrl($asset->image),
                    ];
                    $rewards[] = $reward;
                    @$res[$i]['num'] += $reward['giftnum'] * $reward['gift_coin'];
                }
            }
            $this->setTaskStatus($task, [
                'status' => 1,
                'progress' => (int) ($i / $task['loopCount'] * 100),
            ]);
        }

        foreach ($res as $key => $value) {
            foreach ($value as $k => $v) {
                $outArray['gift_name'] = '总数';
                $outArray['gift_coin'] = $v;
                $string .= implode(",", $outArray) . " \n";
            }
            // $string .= implode(",", ['-', '-']) . "\n";
        }

        return $string;
    }
    public function saveFile($fileName, $text)
    {
        if (!$fileName || !$text) {
            return false;
        }

        if ($fp = fopen($fileName, "w")) {
            if (@fwrite($fp, $text)) {
                fclose($fp);
                return true;
            } else {
                fclose($fp);
                return false;
            }
        }

        return false;
    }

    /**
     * 连续创建目录
     *
     * @param string $dir 目录字符串
     * @param int $mode 权限数字
     * @return boolean
     */
    public function makeDir($dir, $mode = "0777")
    {
        if (!$dir) {
            return false;
        }

        if (!file_exists($dir)) {
            return mkdir($dir, $mode, true);
        } else {
            return true;
        }

    }
}