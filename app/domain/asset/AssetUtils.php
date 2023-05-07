<?php

namespace app\domain\asset;
use app\core\mysql\Sharding;
use app\domain\exceptions\FQException;
use app\domain\user\UserRepository;
use app\utils\StringUtil;

class AssetUtils
{
    public static function makeGiftAssetId($giftKindId) {
        return 'gift:' . $giftKindId;
    }

    public static function makeBankAssetId($accountTypeId) {
        return 'bank:' . $accountTypeId;
    }

    public static function makePropAssetId($propKindId) {
        return 'prop:' . $propKindId;
    }

    public static function isPropAsset($assetId) {
        return StringUtil::startsWith($assetId, 'prop:');
    }

    public static function isGiftAsset($assetId) {
        return StringUtil::startsWith($assetId, 'gift:');
    }

    public static function isBank($assetId) {
        return StringUtil::startsWith($assetId, 'bank:');
    }

    public static function getPropKindIdFromAssetId($assetId) {
        if (AssetUtils::isPropAsset($assetId)) {
            return intval(substr($assetId, 5));
        }
        return null;
    }

    public static function getGiftKindIdFromAssetId($assetId) {
        if (AssetUtils::isGiftAsset($assetId)) {
            return intval(substr($assetId, 5));
        }
        return null;
    }

    //1头像，气泡框 2金币 3礼物
    public static function getAssetTypeFromAssetId($assetId) {
        if (AssetUtils::isPropAsset($assetId)) {
            return 1;
        }elseif (AssetUtils::isGiftAsset($assetId)){
            return 3;
        }elseif ($assetId == AssetKindIds::$COIN){
            return 2;
        }
        return null;
    }

    public static function getAsset($userId, $assetId, $timestamp) {
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use($userId, $assetId, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $userAssets = $user->getAssets();
                return $userAssets->balance($assetId, $timestamp);
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function addAsset($userId, $assetId, $count, $timestamp, $biEvent) {
        if ($count < 0) {
            throw new FQException('数量错误', 500);
        }
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use($userId, $assetId, $count, $timestamp, $biEvent) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $userAssets = $user->getAssets();
                $userAssets->add($assetId, $count, $timestamp, $biEvent);
                $balance = $userAssets->balance($assetId, $timestamp);
                return [$count, $balance];
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function addAssets($userId, $assetList, $timestamp) {
        foreach ($assetList as list($assetId, $count, $biEvent)) {
            if ($count < 0) {
                throw new FQException('数量错误', 500);
            }
        }
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use($userId, $assetList, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $userAssets = $user->getAssets();
                foreach ($assetList as list($assetId, $count, $biEvent)) {
                    $userAssets->add($assetId, $count, $timestamp, $biEvent);
                }
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function consumeAsset($userId, $assetId, $count, $timestamp, $biEvent) {
        if ($count < 0) {
            throw new FQException('数量错误', 500);
        }
        try {
            return Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function () use($userId, $assetId, $count, $timestamp, $biEvent) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $userAssets = $user->getAssets();
                $balance = $userAssets->balance($assetId, $timestamp);
                if ($count > 0 && $balance < $count) {
                    Sharding::getInstance()->getConnectModel('userMaster', $userId)->rollback();
                    return [0, $balance];
                }
                $userAssets->consume($assetId, $count, $timestamp, $biEvent);
                $balance = $userAssets->balance($assetId, $timestamp);
                return [$count, $balance];
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public static function consumeAssets($userId, $assetList, $timestamp) {
        foreach ($assetList as list($assetId, $count, $biEvent)) {
            if ($count < 0) {
                throw new FQException('数量错误', 500);
            }
        }
        try {
            Sharding::getInstance()->getConnectModel('userMaster', $userId)->transaction(function() use($userId, $assetList, $timestamp) {
                $user = UserRepository::getInstance()->loadUser($userId);
                if ($user == null) {
                    throw new FQException('用户不存在', 500);
                }

                $userAssets = $user->getAssets();
                foreach ($assetList as list($assetId, $count, $biEvent)) {
                    $userAssets->consume($assetId, $count, $timestamp, $biEvent);
                }
            });
        } catch (\Exception $e) {
            throw $e;
        }
    }
}

