<?php

namespace app\api\view\v1;

use app\domain\guild\cache\RecreationHotRoomCache;
use app\domain\room\conf\RoomMode;
use app\domain\room\conf\RoomTag;
use app\domain\room\service\RoomService;
use app\query\room\cache\GuildQueryRoomModelCache;
use app\query\room\model\QueryRoom;
use app\query\room\service\QueryRoomService;
use app\service\CommonCacheService;
use app\query\user\cache\UserModelCache;
use app\utils\CommonUtil;

class RoomView
{
    public static function viewPartyQueryRoom(QueryRoom $queryRoom, $source = "")
    {
        $queryRoom->tagImage = RoomTag::getInstance()->getSourceImageForId($queryRoom->tagId, $source);
        $queryRoom->tabIcon = RoomMode::getInstance()->getSourceImageForId($queryRoom->roomType, $source);
        $pkStatus = RoomService::getInstance()->getRoomPkstatus($queryRoom->roomId);
        $liveUserIDs = CommonCacheService::getInstance()->getLiveUserIDbyRoom($queryRoom->roomId);
        $isLive = 0;
        $micUsers = [];
        $userInfo = UserModelCache::getInstance()->getUserInfo($queryRoom->ownerUserId);
        if (!empty($liveUserIDs)){
            $isLive = 1;
            $userModels = UserModelCache::getInstance()->findList($liveUserIDs);
            foreach ($userModels as $key=>$user) {
                $micUsers[$key]['user_id'] = $user->userId;
                $micUsers[$key]['avatar_image'] = CommonUtil::buildImageUrl($user->avatar);
                $micUsers[$key]['nickname'] = $user->nickname;
            }
        }
        return [
            'room_id' => intval($queryRoom->roomId),
            'room_pretty_id' => intval($queryRoom->prettyRoomId),
            'room_name' => $queryRoom->roomName,
            'room_type' => $queryRoom->roomTypeName,
            'room_mode' => $queryRoom->roomMode,
            'room_lock' => intval($queryRoom->lock),
            'visitor_number' => formatNumberLite($queryRoom->visitorNumber),
            'is_live' => $isLive,
            'room_image' => CommonUtil::buildImageUrl($queryRoom->ownerAvatar),
            'avatar_image' => CommonUtil::buildImageUrl($queryRoom->ownerAvatar),
            'nickname' => $queryRoom->ownerNickname,
            'sex' => intval($userInfo->sex),
            'tag_image' => CommonUtil::buildImageUrl($queryRoom->tagImage),
            'tab_icon' => CommonUtil::buildImageUrl($queryRoom->tabIcon),
            'popularNumber' => $queryRoom->popularNumber,
            'pk_status' => $pkStatus,
            'mic_user_list' => $micUsers,
        ];
    }

    /**
     * 用于app提审中 对数据进行处理
     * @param object $queryRoom
     * @param string $source
     * @return array
     */
    public static function viewTsPartyQueryRoom($queryRoom, $source = "")
    {
        $queryRoom->tagImage = RoomTag::getInstance()->getSourceImageForId($queryRoom->tagId, $source);
        $queryRoom->tabIcon = RoomMode::getInstance()->getSourceImageForId($queryRoom->roomType, $source);
        $pkStatus = RoomService::getInstance()->getRoomPkstatus($queryRoom->roomId);
        $userModel = UserModelCache::getInstance()->getUserInfo($queryRoom->userId);
        $model = new RecreationHotRoomCache($queryRoom->roomId);
        $queryRoom->popularNumber = (int)$model->getHotSum();
        return [
            'room_id' => intval($queryRoom->roomId),
            'room_pretty_id' => intval($queryRoom->prettyRoomId),
            'room_name' => $queryRoom->name,
            'room_type' => $queryRoom->roomType,
            'room_lock' => intval($queryRoom->lock),
            'visitor_number' => formatNumberLite($queryRoom->visitorNumber),
            'is_live' => 1,
            'room_image' => CommonUtil::buildImageUrl($userModel->avatar),
            'avatar_image' => CommonUtil::buildImageUrl($userModel->avatar),
            'nickname' => $userModel->nickname,
            'tag_image' => CommonUtil::buildImageUrl($queryRoom->tagImage),
            'tab_icon' => CommonUtil::buildImageUrl($queryRoom->tabIcon),
            'popularNumber'=>$queryRoom->popularNumber,
            'pk_status'=>$pkStatus,
        ];
    }

    public static function viewPersonQueryRoom(QueryRoom $queryRoom, $source = "")
    {
        $queryRoom->tabIcon = RoomMode::getInstance()->getSourceImageForId($queryRoom->roomType, $source);
        $isLive = CommonCacheService::getInstance()->getRoomIsLive($queryRoom->roomId);
        return [
            'room_id' => intval($queryRoom->roomId),
            'room_name' => $queryRoom->roomName,
            'room_mode' => intval($queryRoom->roomMode),
            'room_type' => $queryRoom->roomType,
            'room_tags' => "",
            'user_id' => intval($queryRoom->ownerUserId),
            'room_lock' => intval($queryRoom->lock),
            'visitor_number' => formatNumberLite($queryRoom->visitorNumber),
            'is_live' => $isLive,
            'room_image' => CommonUtil::buildImageUrl($queryRoom->ownerAvatar),
            'nickname' => $queryRoom->ownerNickname,
            'tag_image' => CommonUtil::buildImageUrl($queryRoom->tagImage),
            'tab_icon' => CommonUtil::buildImageUrl($queryRoom->tabIcon),
            'type' => 1,
            'online' => intval($queryRoom->online),
        ];
    }

    /**
     * 用于app提审中 对数据进行处理
     * @param object $queryRoom
     * @param string $source
     * @return array
     */
    public static function viewTsPersonQueryRoom($queryRoom, $source = "")
    {
        $queryRoom->tagImage = RoomTag::getInstance()->getSourceImageForId($queryRoom->tagId, $source);
        $queryRoom->tabIcon = RoomMode::getInstance()->getSourceImageForId($queryRoom->roomType, $source);
        $userModel = UserModelCache::getInstance()->getUserInfo($queryRoom->userId);
        return [
            'room_id' => intval($queryRoom->roomId),
            'room_name' => $queryRoom->name,
            'room_mode' => intval($queryRoom->mode),
            'room_type' => $queryRoom->roomType,
            'room_tags' => "",
            'user_id' => intval($queryRoom->userId),
            'room_lock' => intval($queryRoom->lock),
            'visitor_number' => formatNumberLite($queryRoom->visitorNumber),
            'is_live' => intval($queryRoom->liveType),
            'room_image' => CommonUtil::buildImageUrl($userModel->avatar),
            'nickname' => $userModel->nickname,
            'tag_image' => CommonUtil::buildImageUrl($queryRoom->tagImage),
            'tab_icon' => CommonUtil::buildImageUrl($queryRoom->tabIcon),
            'type' => 1,
            'online' => GuildQueryRoomModelCache::getInstance()->getOnlineRoomUserCount($queryRoom->roomId),
        ];
    }


    public static function viewHotRoomLiteData(QueryRoom $queryRoom, $source)
    {
        $queryRoom->tagImage = RoomTag::getInstance()->getSourceImageForId($queryRoom->tagId, $source);
        $isLive = CommonCacheService::getInstance()->getRoomIsLive($queryRoom->roomId);
        return [
            'room_id' => intval($queryRoom->roomId),
            'room_name' => $queryRoom->roomName,
            'room_mode' => intval($queryRoom->roomMode),
            'room_type' => $queryRoom->roomTypeName,
            'room_tags' => $queryRoom->roomTypeName,
            'user_id' => intval($queryRoom->ownerUserId),
            'room_lock' => intval($queryRoom->lock),
            'visitor_number' => formatNumberLite($queryRoom->visitorNumber),
            'visitor_externnumber' => formatNumberLite($queryRoom->visitorNumber),
            'redu' => formatNumberLite($queryRoom->visitorNumber),
            'visitor_users' => 0,
            'visitor_number_str' => formatNumberLite($queryRoom->visitorNumber),
            'is_live' => $isLive,
            'room_image' => CommonUtil::buildImageUrl($queryRoom->ownerAvatar),
            'nickname' => $queryRoom->ownerNickname,
            'tag_image' => CommonUtil::buildImageUrl($queryRoom->tagImage),
            'tab_icon' => CommonUtil::buildImageUrl($queryRoom->tabIcon),
            'type' => 1,
        ];
    }


    public static function viewMuaHotRoomLiteData(QueryRoom $queryRoom, $source = '')
    {
        $queryRoom->tagImage = RoomTag::getInstance()->getSourceImageForId($queryRoom->tagId, $source);
        $queryRoom->tabIcon = RoomMode::getInstance()->getSourceImageForId($queryRoom->roomType, $source);
        $pkStatus = RoomService::getInstance()->getRoomPkstatus($queryRoom->roomId);
        $isLive = CommonCacheService::getInstance()->getRoomIsLive($queryRoom->roomId);
        return [
            'room_id' => intval($queryRoom->roomId),
            'room_name' => $queryRoom->roomName,
            'room_mode' => intval($queryRoom->roomMode),
            'room_type' => $queryRoom->roomTypeName,
            'room_tags' => $queryRoom->roomTypeName,
            'user_id' => intval($queryRoom->ownerUserId),
            'room_lock' => intval($queryRoom->lock),
            'visitor_number' => self::channelToVisitor($queryRoom),
            'visitor_externnumber' => self::channelToVisitor($queryRoom),
            'visitor_users' => 0,
            'visitor_number_str' => formatNumberLite($queryRoom->visitorNumber),
            'is_live' => $isLive,
            'room_image' => CommonUtil::buildImageUrl($queryRoom->ownerAvatar),
            'nickname' => $queryRoom->ownerNickname,
            'tag_image' => CommonUtil::buildImageUrl($queryRoom->tagImage),
            'tab_icon' => CommonUtil::buildImageUrl($queryRoom->tabIcon),
            'type' => 1,
            'pk_status' => $pkStatus,
        ];
    }


    /**
     * @info 兼容不同渠道的热度类型
     * @param QueryRoom $queryRoom
     * @param string $channel
     * @return int|string
     */
    private static function channelToVisitor(QueryRoom $queryRoom)
    {
        return intval($queryRoom->visitorNumber) > 0 ? intval($queryRoom->visitorNumber) : 0;
    }


    /**
     * @param $roomInfo
     * @return array
     */
    public static function roomInfoLiteView($roomInfo)
    {
        return [
            'room_id' => $roomInfo['id'],
            'room_name' => $roomInfo['room_name'],
            'room_type' => $roomInfo['room_type'],
            'room_desc' => $roomInfo['room_desc'],
            'room_welcomes' => $roomInfo['room_welcomes'],
            'background_image' => $roomInfo['background_image'],
            'room_lock' => $roomInfo['room_lock'],
            'is_wheat' => $roomInfo['is_wheat'],
            'guild_id' => $roomInfo['guild_id'],
            'room_wall' => $roomInfo['room_wall'],
            'audit_actions' => $roomInfo['audit_actions']
        ];
    }

    /**
     * @param QueryRoom $queryRoom
     * @return array
     */
    public static function viewQueryRoom(QueryRoom $queryRoom)
    {
        $isLive = CommonCacheService::getInstance()->getRoomIsLive($queryRoom->roomId);
        return [
            'room_id' => $queryRoom->roomId,
            'room_name' => $queryRoom->roomName,
            'room_type' => $queryRoom->roomMode,
            'room_lock' => $queryRoom->lock,
            'visitor_number' => $queryRoom->visitorNumber,
            'is_live' => $isLive,
            'room_image' => CommonUtil::buildImageUrl($queryRoom->ownerAvatar),
            'nickname' => $queryRoom->ownerNickname,
            'pretty_room_id' => $queryRoom->prettyRoomId
        ];
    }


    /**
     * @param QueryRoom $queryRoom
     * @return array
     */
    public static function viewMyQueryRoom(QueryRoom $queryRoom)
    {
        $ret = self::viewQueryRoom($queryRoom);
        $ret['hx_room'] = $queryRoom->hxRoom;
        return $ret;
    }

    public static function searchRoomListViewLite($roomData, $userId, $source ,$versionCheckStatus=0)
    {
        $roomList = [];
        foreach ($roomData as $partyRoom) {
            if($versionCheckStatus){
                $roomInfo = RoomView::viewTsPartyQueryRoom($partyRoom, $source);
            }else{
                $roomInfo = RoomView::viewPartyQueryRoom($partyRoom, $source);
            }
            $roomInfo['redpackets'] = QueryRoomService::getInstance()->hasRedPacket($partyRoom->roomId, $userId);
            $roomList[] = $roomInfo;
        }
        return $roomList;
    }

    public static function searchUserView($user)
    {
        $current_room_id = CommonCacheService::getInstance()->getUserCurrentRoom($user->userId);
        $onMic = CommonCacheService::getInstance()->getUserOnMic($user->userId);
        return [
            'id' => $user->userId,
            'pretty_id' => $user->prettyId,
            'nickname' => $user->nickname,
            'avatar' => CommonUtil::buildImageUrl($user->avatar),
            'lv_dengji' => $user->lvDengji,
            'sex' => $user->sex,
            'is_vip' => $user->vipLevel,
            'room_id' => $current_room_id,
            'on_mic' => $onMic,
        ];
    }

    /**
     * @param $room
     * @return array
     */
    public static function searchViewRoom($room)
    {
        $isLive = CommonCacheService::getInstance()->getRoomIsLive($room->roomId);
        return [
            'id' => $room->roomId,
            'pretty_room_id' => $room->prettyRoomId,
            'room_name' => $room->roomName,
            'room_image' => CommonUtil::buildImageUrl($room->ownerAvatar),
            'is_live' => $isLive,
            'owner_user_id' => $room->ownerUserId,
            'redu'=>$room->visitorNumber
        ];
    }

}



































