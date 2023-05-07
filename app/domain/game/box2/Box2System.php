<?php


namespace app\domain\game\box2;


use app\domain\exceptions\FQException;
use app\domain\gift\GiftSystem;
use app\domain\mall\MallSystem;
use app\utils\ArrayUtil;
use think\facade\Log;

class Box2System
{
    protected static $instance;
    public static $maxBaolv = 1.12;
    public $isOpen = 1;
    public $defaultCounts = [1, 10, 66];
    public $customCountRange = [5, 200];
    // map<boxId, Box2>
    public $boxMap = null;

    // 全服公屏消息最小礼物价值
    public $fullPublicGiftValue = 0;
    // 房间飘屏最小礼物价值
    public $fullFlutterGiftValue = 0;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new Box2System();
            self::$instance->loadFromJson();
        }
        return self::$instance;
    }

    public function findBox($boxId) {
        return ArrayUtil::safeGet($this->boxMap, $boxId);
    }

    public static function calcBaolv($price, $giftMap) {
        $consume = 0;
        $reward = 0;
        foreach ($giftMap as $giftId => $count) {
            $consume += $count * $price;
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($giftKind != null) {
                $reward += $giftKind->price != null ? $count * $giftKind->price->count : 0;
            }
        }
        return [
            $consume, $reward
        ];
    }

    public static function setConf($conf) {
        self::decodeConf($conf);
        Config::getInstance()->setBoxConf($conf);
    }

    private static function decodeConf($conf) {
        $boxMap = [];
        $boxesConf = $conf['boxes'];
        foreach ($boxesConf as $boxConf) {
            $box = new Box2();
            $box->decodeFromJson($boxConf);
            if (array_key_exists($box->boxId, $boxMap)) {
                Log::warning(sprintf('Box2System::decodeConf DuplicateBox boxId=%s', $box->boxId));
                throw new FQException('转盘id配置重复，id='.$box->boxId, 500);
            }
            $boxMap[$box->boxId] = $box;
            self::checkBaolv($box);
        }

        $countConf = $conf['count'];
        if (empty($countConf)) {
            Log::error(sprintf('Box2System::decodeConf NotFoundCount'));
            throw new FQException('价格次数配置错误', 500);
        }

        $defaultCountList = $countConf['default'];
        foreach ($defaultCountList as $defaultCount) {
            if (!is_int($defaultCount)) {
                Log::error(sprintf('Box2System::decodeConf BadDefaultCount default=%s', json_encode($defaultCountList)));
                throw new FQException('价格次数配置错误', 500);
            }
        }

        $customCountRange = $countConf['custom'];
        if (count($customCountRange) != 2) {
            Log::error(sprintf('Box2System::decodeConf BadCustomCountLen custom=%s', json_encode($customCountRange)));
            throw new FQException('价格次数配置错误', 500);
        }

        foreach ($customCountRange as $customCount) {
            if (!is_int($customCount)) {
                Log::error(sprintf('Box2System::decodeConf BadCustomCountValue custom=%s', json_encode($defaultCountList)));
                throw new FQException('价格次数配置错误', 500);
            }
        }

        $fullPublicGiftValue = max($conf['fullPublicGiftValue'], 500);
        $fullFlutterGiftValue = max($conf['fullFlutterGiftValue'], 500);

        $isOpen = ArrayUtil::safeGet($conf, 'isOpen', 1);

        return [
            'boxMap' => $boxMap,
            'isOpen' => $isOpen,
            'count' => $countConf,
            'fullPublicGiftValue' => $fullPublicGiftValue,
            'fullFlutterGiftValue' => $fullFlutterGiftValue
        ];
    }

    private static function checkBaolv($box) {
        foreach ($box->rewardPoolMap as $poolId => $rewardPool) {
            list($consume, $reward) = self::calcBaolv($box->price, $rewardPool->giftMap);
            $baolv = floatval($reward) / floatval($consume);
            if ($baolv > self::$maxBaolv) {
                Log::error(sprintf('Box2System::checkBaolv boxId=%d poolId=%d baolv=%d:%d:%.6f', $box->boxId, $poolId,
                    $reward, $consume, $baolv));
                throw new FQException('爆率配置错误,poolId='.$poolId, 500);
            }
        }
    }

    private function loadFromJson() {
        $conf = Config::getInstance()->getBoxConf();

        $decodedConf = $this->decodeConf($conf);

        $this->isOpen = $decodedConf['isOpen'];
        $this->boxMap = $decodedConf['boxMap'];
        $this->fullPublicGiftValue = $decodedConf['fullPublicGiftValue'];
        $this->fullFlutterGiftValue = $decodedConf['fullFlutterGiftValue'];

        Log::info(sprintf('Box2System::loadFromJson ok isOpen=%d fullPublicGiftValue=%d fullFlutterGiftValue=%d boxIds=%s',
            $this->isOpen, $this->fullPublicGiftValue, $this->fullFlutterGiftValue, json_encode(array_keys($this->boxMap))));
    }
}