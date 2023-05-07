<?php


namespace app\domain\game\gashapon;


use app\domain\asset\AssetItem;
use app\domain\asset\AssetSystem;
use app\domain\asset\AssetUtils;
use app\domain\exceptions\FQException;
use think\facade\Log;

class Lottery
{
    // 奖励id
    public $lotteryId = 0;
    //权重
    public $weight= 0;
    //奖励
    public $reward= null;

    public function loadFromJson($objson) {
        $lotteryId = $objson['reward']['assetId'];
        if (empty(AssetSystem::getInstance()->findAssetKind($lotteryId)) or !AssetUtils::isPropAsset($lotteryId)){
            Log::error(sprintf('Lottery::loadFromJson UnknownLotteryId=%d', $lotteryId));
            throw new FQException('没有该资产:'.$lotteryId, 500);
        }
        $this->lotteryId = $lotteryId;
        $this->weight = $objson['weight'];
        $this->reward = new AssetItem($objson['reward']['assetId'], $objson['reward']['count']);
    }
}