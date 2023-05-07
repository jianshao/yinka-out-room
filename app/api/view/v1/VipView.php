<?php


namespace app\api\view\v1;


class VipView
{
    public static function viewVip($vipModel, $timestamp)
    {
        $vipExp = 0;
        // 仅剩余1天才会展示这个字段
        $vipLessDayTime = 0;
        if ($vipModel->level > 0 && $vipModel->vipExpiresTime > 0) {
            $vipExp = floor(($vipModel->vipExpiresTime - $timestamp) / 86400);
            $vipDiffTime = $vipModel->vipExpiresTime - $timestamp;
            if ($vipDiffTime > 0 && $vipDiffTime < 86400){
                $vipLessDayTime = $vipDiffTime;
            }
        }
        $svipExp = 0;
        // 仅剩余1天才会展示这个字段
        $svipLessDayTime = 0;
        if ($vipModel->level > 0 && $vipModel->svipExpiresTime > 0) {
            $svipExp = floor(($vipModel->svipExpiresTime - $timestamp) / 86400);
            $svipDiffTime = $vipModel->svipExpiresTime - $timestamp;
            if ($svipDiffTime > 0 && $svipDiffTime < 86400){
                $svipLessDayTime = $svipDiffTime;
            }
        }
        $vipExpirationTime = 0;
        if ($vipModel->vipExpiresTime > 0 && $timestamp > $vipModel->vipExpiresTime) {
            $vipExpirationTime = floor(($timestamp - $vipModel->vipExpiresTime) / 86400);
        }

        $svipExpirationTime = 0;
        if ($vipModel->svipExpiresTime > 0 && $timestamp > $vipModel->svipExpiresTime) {
            $svipExpirationTime = floor(($timestamp - $vipModel->svipExpiresTime) / 86400);
        }

        return [
            'vip_is_expire' => $vipModel->vipExpiresTime > $timestamp ? 0 : 1,  // 是否过期  0没有  1过期
            'svip_is_expire' => $vipModel->svipExpiresTime > $timestamp ? 0 : 1,
            'vip_exp' => $vipExp,   // 剩余天数
            'svip_exp' => $svipExp,
            'vip_expiration_time' => $vipExpirationTime,  // 过期天数
            'svip_expiration_time' => $svipExpirationTime,
            'vip_less_day_time' => $vipLessDayTime,  // 少于一天的时间
            'svip_less_day_time' => $svipLessDayTime,
            'is_vip' => $vipModel->level, // 当前会员级别  0.没有，1.vip   2.svip
            'vip_expire_time' => $vipModel->vipExpiresTime > $timestamp ? $vipModel->vipExpiresTime : 0,  // 到期的时间戳
            'svip_expire_time' => $vipModel->svipExpiresTime > $timestamp ? $vipModel->svipExpiresTime: 0,
        ];
    }
}