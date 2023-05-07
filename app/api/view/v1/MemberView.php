<?php


namespace app\api\view\v1;


use app\domain\room\dao\RoomModelDao;
use app\query\room\service\QueryRoomService;
use app\query\room\service\QueryRoomTypeService;
use app\domain\user\model\UserModel;
use app\service\CommonCacheService;
use app\utils\CommonUtil;

class MemberView
{
    public static function encodeRankData($userModel, $bean, $roomId)
    {
        return [
            'userId' => intval($userModel->userId),
            'prettyId' => intval($userModel->prettyId),
            'nickName' => $userModel->nickname,
            'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
            'prettyAvatar' => CommonUtil::buildImageUrl($userModel->prettyAvatar),
            'lvDengji' => intval($userModel->lvDengji),
            'userLv' => intval($userModel->lvDengji),
            'vipLv' => intval($userModel->vipLevel),
            'dukeId' => intval($userModel->dukeLevel),
            'sex' => intval($userModel->sex),
            'coin' => $bean,
            'roomId' => $roomId
        ];
    }


    /**
     * @Info cove用户信息 usercachemodel
     * @param UserModel $userModel
     * @return array
     */
    public static function onlineUserView(UserModel $userModel, $onlineStatus)
    {
        $roomId = CommonCacheService::getInstance()->getUserCurrentRoom($userModel->userId);
        $roomType = QueryRoomService::getInstance()->findRoomTypeByRoomIdForCache($roomId);
        $roomType = isset($roomType->roomType) ? $roomType->roomType : 0;
        $roomTypeModel = QueryRoomTypeService::getInstance()->loadRoomTypeForCache($roomType);
        $userData = UserView::viewOnlineUser($roomId, $userModel, $roomTypeModel);
        $userData['hotRoomList'] = [];
        $userData['type'] = 1;
        $userData['onlineType'] = self::getOnlineType($roomId, $onlineStatus);
        return $userData;
    }


    private static function getOnLineType($roomId, $onlineStatus)
    {
        if (!empty($roomId)) {
            return 2;
        }
        if ($onlineStatus == 1) {
            return 1;
        }
        return 0;
    }
}