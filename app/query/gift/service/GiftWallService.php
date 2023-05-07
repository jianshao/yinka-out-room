<?php


namespace app\query\gift\service;


use app\domain\exceptions\FQException;
use app\query\gift\dao\GiftWallModelDao;
use app\domain\gift\GiftSystem;
use app\utils\ArrayUtil;
use app\view\GiftCollectionView;
use app\view\GiftWallView;
use think\facade\Log;

class GiftWallService
{
    protected static $instance;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GiftWallService();
        }
        return self::$instance;
    }

    public function getGiftWall($userId) {
        try {
            $receiveGiftMap = GiftWallModelDao::getInstance()->loadGiftWallByUserId($userId);
            $giftWalls = GiftSystem::getInstance()->getGiftWalls();
            $wallGifts = [];
            foreach ($giftWalls as $gift) {
                $count = 0;
                $receiveGift = ArrayUtil::safeGet($receiveGiftMap, $gift->kindId);
                if ($receiveGift != null) {
                    $count = $receiveGift->count;
                }
                $wallGifts[] = GiftWallView::encodeGiftWall($gift, $count);
            }
            return  [
                'list' => $wallGifts,
            ];
        }catch (\Exception $e) {
            Log::error(sprintf('GiftWallService getGiftWall error userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw new FQException('未知错误请重试',500);
        }
    }

    public function getGiftCollection($userId) {
        $result = [];
        try {
            $receiveGiftMap = GiftWallModelDao::getInstance()->loadGiftWallByUserId($userId);
            $giftCollections = GiftSystem::getInstance()->getGiftCollectionMap();
            foreach ($giftCollections as $collection) {
                $userHaveCount = 0;
                $collectionGifts = [];
                foreach ($collection->gifts as $gift) {
                    $giftKind = ArrayUtil::safeGet($gift,'gift');
                    $count = 0;
                    $receiveGift = ArrayUtil::safeGet($receiveGiftMap, $giftKind->kindId);
                    if ($receiveGift != null) {
                        $count = $receiveGift->count;
                        $userHaveCount += 1;
                    }
                    $collectionGifts[] = GiftCollectionView::encodeGiftCollection($gift, $count);
                }
                if (count($collectionGifts) > 0) {
                    $result[] = [
                        'name' => $collection->displayName,
                        'giftCount' => count($collectionGifts),
                        'userHaveCount' => $userHaveCount,
                        'list' => $collectionGifts,
                    ];
                }
            }
            return $result;
        } catch (\Exception $e) {
            Log::error(sprintf('GiftWallService getGiftCollection error userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            throw new FQException('未知错误请重试');
        }
    }
}