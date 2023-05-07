<?php


namespace app\domain\game;

use app\common\RedisCommon;
use app\domain\mall\MallIds;
use app\domain\mall\MallSystem;
use app\domain\mall\service\MallService;

use app\domain\exceptions\FQException;
use think\facade\Log;

class GameService
{
    protected static $instance;
    protected static $goodsId= 46;
    public $priceAssetId = 'bank:game:score';
    public $goods= null;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new GameService();
            self::$instance->makeGoods();
        }
        return self::$instance;
    }

    public function buildGameAutoBuyKey($userId) {
        return 'game_autobuy:'.$userId;
    }

    public function getGoods(){
        return $this->goods;
    }

    public function makeGoods(){
        $goods = MallSystem::getInstance()->findGoods(self::$goodsId);
        if ($goods == null) {
            Log::warning(sprintf('GameService::makeGoods UnknownGoods goodsId=%d', self::$goodsId));
            throw new FQException('配置错误', 500);
        }

        if ($goods->deliveryAsset->assetId != $this->priceAssetId) {
            Log::warning(sprintf('GameService::makeGoods DiffAssetId goodsId=%d goodsAssetId=%s priceAssetId=%s',
                self::$goodsId, $goods->deliveryAsset->assetId, $this->priceAssetId));
            throw new FQException('配置错误', 500);
        }

        $this->goods = $goods;
    }

    public function buyGoods($userId, $count, $roomId) {
        // 购买商品
        return MallService::getInstance()->buyGoodsByGoods($userId, $this->goods,
            $count, MallIds::$GAME, 'game', $roomId);
    }

    public function setAutoBuy($userId, $autoBuy) {
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->set($this->buildGameAutoBuyKey($userId), $autoBuy);
    }

    public function getAutoBuy($userId) {
        $redis = RedisCommon::getInstance()->getRedis();
        $autoBuy = $redis->get($this->buildGameAutoBuyKey($userId));
        return $autoBuy != null ? intval($autoBuy) : 0;
    }
}