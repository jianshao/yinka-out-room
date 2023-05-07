<?php


namespace app\domain\asset;


use app\domain\exceptions\FQException;
use think\facade\Log;

class AssetKindSvipMonth extends AssetKind
{
    public function __construct($kindId) {
        $this->kindId = $kindId;
        $this->displayName = 'svip';
        $this->unit = '月';
    }

    public function add($userAssets, $count, $timestamp, $biEvent) {
        $vip = $userAssets->getVip($timestamp);
        $expiresTime = $vip->getSvipExpiresTime();
        if ($expiresTime < $timestamp) {
            $expiresTime = $timestamp;
        }
        $newExpiresTime = strtotime("+$count month", $expiresTime);
        $days = ($newExpiresTime - $expiresTime) / 86400;

        Log::info(sprintf('AssetKindSvipMonth.add userId=%d count=%d expiresTime=%d newExpires=%d days=%d',
            $userAssets->getUserId(), $count, $expiresTime, $newExpiresTime, $days));

        return $vip->addSvip($days, $timestamp, $biEvent);
    }

    public function consume($userAssets, $count, $timestamp, $biEvent) {
        throw new FQException('接口未实现', 500);
    }

    public function balance($userAssets, $timestamp) {
        return $userAssets->getVip($timestamp)->svipBalance($timestamp);
    }
}