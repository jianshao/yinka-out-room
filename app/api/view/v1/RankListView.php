<?php


namespace app\api\view\v1;


use app\utils\CommonUtil;

// 排行榜
class RankListView
{


    /**
     * @param $userModel
     * @param $bean
     * @param $roomId
     * @return array
     */
    public static function encodeRankData($userModel, $bean, $roomId)
    {
        $coinUnit = self::convertCoinUnit($bean);
        return [
            'user_id' => $userModel->userId,
            'pretty_id' => $userModel->prettyId,
            'nickname' => $userModel->nickname,
            'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
            'pretty_avatar' => CommonUtil::buildImageUrl($userModel->prettyAvatar),
            'lv_dengji' => $userModel->lvDengji,
            'user_lv' => $userModel->lvDengji,
            'vip_lv' => $userModel->vipLevel,
            'is_vip' => $userModel->vipLevel,
            'duke_id' => $userModel->dukeLevel,
            'sex' => $userModel->sex,
            'coin' => $bean,
            'coin_unit' => $coinUnit,
            'room_id' => $roomId
        ];
    }

    public static function encodeRankIconData($userModel, $bean, $roomId)
    {
        return [
            'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
        ];
    }


    /**
     * @param $bean
     * @return string
     */
    private static function convertCoinUnit($bean)
    {
        if ($bean === "--")
            return $bean;

        return formatNumberLite($bean);
    }
}