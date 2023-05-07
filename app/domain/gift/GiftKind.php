<?php


namespace app\domain\gift;

use app\domain\asset\AssetItem;
use app\domain\asset\AssetKindIds;
use app\domain\asset\rewardcontent\ContentRegister;
use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;
use app\utils\ClassRegister;
use think\facade\Log;

class GiftKind
{
    public $kindId = 0;                    //礼物id
    public $name = '';                     //礼物名称
    public $classType = 1;                     //礼物名称
    public $unit = '';                      //单位
    public $image = '';                     //礼物图标
    public $animation = '';                 //礼物动画
    public $giftAnimation = '';             //动画礼物地址
    public $giftMp4Animation = '';          //动画礼物地址mp4
    public $mp4Rate = '';                   //动画礼物mp4比例
    public $intro = '';                     //介绍
    public $classification = '';              //分类
    public $tags = '';                     //礼物角标
    public $price = null;                  //价格
    public $deliveryCharm = 0;              //魅力值
    public $giftIdWeightList = null;         //礼物的权重list
    public $giftWeightList = null;          //礼物盒子列表 [[giftKind, totalweigt, weight]]
    public $boxIntroduction = null;          //礼物盒子简介
    public $totalWeight = 0;                //总数
    public $vipLevel = 0;                   //vip等级
    public $dukeLevel = 0;                  //爵位等级
    public $senderAssets = null;            //发送者增加的资源
    public $receiverAssets = null;          //接受者增加的资源
    public $functions = null;               //礼物房间功能 send-能赠送 open-能打开
    public $actionMap = [];                 //礼物背包功能 breakup-分解
    public $gainContents = null;            //打开获得的资产
    public $superRewardRule = null;            //礼物规则限制 （魔方中会使用）
    public $superRewardList = null;         //超级大礼
    public $clientParams = null;            //礼物透传字段

    public function randomGift() {
        if ($this->giftIdWeightList == null) {
            assert($this->giftWeightList != null);
        }
        $value = random_int(1, $this->totalWeight);
        foreach ($this->giftWeightList as $giftWeight) {
            if ($value <= $giftWeight[1]) {
                return $giftWeight[0];
            }
        }
        assert(0);
    }

    public function isBox() {
        return $this->giftWeightList != null;
    }

    public function getPriceByAssetId($assetId) {
        if ($this->price && $this->price->assetId == $assetId) {
            return $this->price->count;
        }
        return 0;
    }

    public function getReceiverAssetCount($assetId) {
        if ($this->receiverAssets != null) {
            return AssetItem::calcAssetCount($this->receiverAssets, $assetId);
        }
        return 0;
    }

    public function decodeFromJson($jsonObj) {
        $this->kindId = $jsonObj['giftId'];
        $this->name = $jsonObj['name'];
        $this->classType = $jsonObj['class_type'] ?? 1;
        $this->unit = $jsonObj['unit'];
        $this->image = ArrayUtil::safeGet($jsonObj, 'image');
        $this->animation = $jsonObj['animation'];
        $this->giftAnimation = $jsonObj['giftAnimation'];
        $this->giftMp4Animation = ArrayUtil::safeGet($jsonObj, 'giftMp4Animation', '');
        $this->mp4Rate = ArrayUtil::safeGet($jsonObj, 'mp4Rate', 0);
        $this->intro = ArrayUtil::safeGet($jsonObj, 'intro', '');
        $this->classification = ArrayUtil::safeGet($jsonObj, 'classification', '');
        $this->tags = ArrayUtil::safeGet($jsonObj, 'tags', '');
        $this->deliveryCharm = (int) $jsonObj['charm'];
        $this->vipLevel = ArrayUtil::safeGet($jsonObj, 'vipLevel', 0);
        $this->dukeLevel = ArrayUtil::safeGet($jsonObj, 'dukeLevel', 0);
        if (isset($jsonObj['box'])) {
            $giftIdWeightList = [];
            foreach ($jsonObj['box'] as $giftIdWeight) {
                $giftId = $giftIdWeight['giftId'];
                $weight = $giftIdWeight['weight'];
                if ($weight < 0) {
                    Log::error(sprintf('GiftKindDecode kindId=%d data=%d',
                        $this->kindId, json_encode($giftIdWeight)));
                    throw new FQException('weight less than 0', -1);
                }
                $giftIdWeightList[] = $giftIdWeight;
            }

            if (count($giftIdWeightList) > 0) {
                $this->giftIdWeightList = $giftIdWeightList;
            }
            $this->boxIntroduction['ruleInfo'] = ArrayUtil::safeGet($jsonObj, 'ruleInfo', '花费音豆可购买并赠送幸运盒子给指定的一名用户或多名用户，收到幸运盒子的用户可收获随机开出的礼物，并按照礼物的实际价值增加魅力值等。');
            $this->boxIntroduction['specialInfo'] = ArrayUtil::safeGet($jsonObj, 'specialInfo', '1、幸运盒子仅作为平台内娱乐\n2、用户通过作弊或其他非正常手段获得的奖励，平台有权收回，同依据相关规则对其进行处罚。');
            $this->boxIntroduction['boxBackgrounds'] = ArrayUtil::safeGet($jsonObj, 'boxBackgrounds', []);
        }
        $price = isset($jsonObj['price']) ? $jsonObj['price'] : null;
        if ($price) {
            $this->price = new AssetItem($price['assetId'], intval($price['count']));
        }

        $senderAssets = ArrayUtil::safeGet($jsonObj, 'senderAssets');
        if ($senderAssets) {
            $this->senderAssets = AssetItem::decodeList($senderAssets);
        }

        $receiverAssets = ArrayUtil::safeGet($jsonObj, 'receiverAssets');
        if ($receiverAssets) {
            $this->receiverAssets = AssetItem::decodeList($receiverAssets);
        }

        $functions = ArrayUtil::safeGet($jsonObj, 'functions');
        if($functions){
            $this->functions = $functions;
        }else{
            $this->functions = ['send'];
        }

        if (ArrayUtil::safeGet($jsonObj,'actions')) {
            $this->actionMap = GiftActionRegister::getInstance()->encodeList($jsonObj['actions']);
        }

        if(ArrayUtil::safeGet($jsonObj, 'gainContents')) {
            $this->gainContents = ContentRegister::getInstance()->decodeList($jsonObj['gainContents']);
        }

        if (ArrayUtil::safeGet($jsonObj, 'superRewardRule')) {
            $superReward = new SuperRewardRule();
            $this->superRewardRule = $superReward->decodeFromJson($jsonObj['superRewardRule']);
        }

        if (ArrayUtil::safeGet($jsonObj, 'superRewardList')) {
            $this->superRewardList = AssetItem::decodeList($jsonObj['superRewardList']);
        }

        if (ArrayUtil::safeGet($jsonObj, 'clientParams')) {
            $this->clientParams = $jsonObj['clientParams'];
        }

        if ($this->giftIdWeightList == null
            && $this->price
            && $this->price->assetId == AssetKindIds::$BEAN
            && $this->receiverAssets == null) {
            // 'scale'=>1000,	//豆兑换钻石比例 1:1000
            // 'self_scale'=>0.65,//个人比例
            $diamondAsset = new AssetItem(AssetKindIds::$DIAMOND, intval($this->price->count * config('config.scale') * config('config.self_scale')));
            $this->receiverAssets = [$diamondAsset];
        }

        $this->decodeFromJsonImpl($jsonObj);
    }

    private function superRewardRule() {

    }

    public function isAllMicGift() {
        if ($this->kindId == 569 && !empty($this->clientParams)) {
            if (ArrayUtil::safeGet($this->clientParams, 'sendAllMic')) {
                return true;
            }
        }
        return false;
    }

    public function initWhenLoaded($giftKindMap) {
        if ($this->giftIdWeightList != null) {
            $giftWeightList = [];
            $totalWeight = 0;
            foreach ($this->giftIdWeightList as $giftIdWeight) {
                $giftKind = ArrayUtil::safeGet($giftKindMap, $giftIdWeight['giftId']);
                if ($giftKind == null) {
                    Log::error(sprintf('GiftKindInitWhenLoadUnknownBoxGift giftId=%d boxGiftId=%d',
                        $this->kindId, $giftIdWeight['giftId']));
                    throw new FQException('配置错误', 500);
                }
                if ($giftKind->kindId == $this->kindId) {
                    Log::error(sprintf('GiftKindInitWhenLoadBoxSelf giftId=%d boxGiftId=%d',
                        $this->kindId, $giftIdWeight['giftId']));
                    throw new FQException('配置错误', 500);
                }
                if ($giftKind->giftIdWeightList != null) {
                    Log::error(sprintf('GiftKindInitWhenLoadBoxBox kindId=%d boxGiftId=%d',
                        $this->kindId, $giftIdWeight['giftId']));
                    throw new FQException('配置错误', 500);
                }
                $totalWeight += $giftIdWeight['weight'];
                $giftWeightList[] = [$giftKind, $totalWeight, $giftIdWeight['weight']];
            }
            if ($totalWeight < 1) {
                Log::error(sprintf('GiftKindInitWhenLoadBoxWeightZero kindId=%d', $this->kindId));
                throw new FQException('配置错误', 500);
            }
            $this->giftWeightList = $giftWeightList;
            $this->totalWeight = $totalWeight;
        }
    }

    protected function decodeFromJsonImpl($jsonObj) {

    }

    public function canSendFromBag(){
        return in_array('send', $this->functions);
    }

    public function canOpenFromBag(){
        return in_array('open', $this->functions);
    }
}