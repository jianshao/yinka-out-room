<?php
//
//namespace app\domain\room\model;
//
//class PersonRoomModel extends QueryRoomModel
//{
//    public $roomId = 0;
//    public $guildId = 0;
//    public $prettyRoomId = 0;
//    public $roomName = '';
//    public $roomType = '';
//    public $roomTypeName = '';
//    public $roomMode = 1;
//    public $roomTags = '';
//    public $room = 1;
//    public $lock = 0;
//    public $visitorNumber = 0;
//    public $visitorExternNumber = 0;
//    public $visitorUsers = 0;
//    public $isLive = 0;
//    public $isHot = 0;
//    public $hxRoom = 0;
//    public $image = '';
//    public $tagImage = '';
//    public $tabIcon = '';
//    public $ownerUserId = 0;
//    public $ownerAvatar = '';
//    public $ownerNickname = '';
//    public $type = 0;
//    public $online = 0;
//
//    public function modelToData()
//    {
//        return [
//            'roomId' => $this->roomId,
//            'guildId' => $this->guildId,
//            'prettyRoomId' => $this->prettyRoomId,
//            'roomName' => $this->roomName,
//            'roomType' => $this->roomType,
//            'roomTags' => $this->roomTags,
//            'roomTypeName' => $this->roomTypeName,
//            'roomMode' => $this->roomMode,
//            'lock' => $this->lock,
//            'visitorNumber' => $this->visitorNumber,
//            'visitorExternNumber' => $this->visitorExternNumber,
//            'visitorUsers' => $this->visitorUsers,
//            'isLive' => $this->isLive,
//            'isHot' => $this->isHot,
//            'hxRoom' => $this->hxRoom,
//            'image' => $this->image,
//            'tagImage' => $this->tagImage,
//            'tabIcon' => $this->tabIcon,
//            'ownerUserId' => $this->ownerUserId,
//            'ownerAvatar' => $this->ownerAvatar,
//            'ownerNickname' => $this->ownerNickname,
//            'type' => $this->type,
//            'online' => $this->online,
//        ];
//    }
//
//    public function fromJson($jsonObj)
//    {
//        $this->roomId = $jsonObj['roomId'];
//        $this->guildId = $jsonObj['guildId'];
//        $this->prettyRoomId = $jsonObj['prettyRoomId'];
//        $this->roomName = $jsonObj['roomName'];
//        $this->roomTags = $jsonObj['roomTags'];
//        $this->roomType = $jsonObj['roomType'];
//        $this->roomTypeName = $jsonObj['roomTypeName'];
//        $this->roomMode = $jsonObj['roomMode'];
//        $this->lock = $jsonObj['lock'];
//        $this->visitorNumber = $jsonObj['visitorNumber'];
//        $this->visitorExternNumber = $jsonObj['visitorExternNumber'];
//        $this->visitorUsers = $jsonObj['visitorUsers'];
//        $this->isLive = $jsonObj['isLive'];
//        $this->isHot = $jsonObj['isHot'];
//        $this->hxRoom = $jsonObj['hxRoom'];
//        $this->image = $jsonObj['image'];
//        $this->tagImage = $jsonObj['tagImage'];
//        $this->tabIcon = $jsonObj['tabIcon'];
//        $this->ownerUserId = $jsonObj['ownerUserId'];
//        $this->ownerAvatar = $jsonObj['ownerAvatar'];
//        $this->ownerNickname = $jsonObj['ownerNickname'];
//        $this->type = $jsonObj['type'];
//        $this->online = $jsonObj['online'];
//        return $this;
//    }
//
//    public static function toJsonList($rooms)
//    {
//        $ret = [];
//        foreach ($rooms as $room) {
//            $ret[] = $room->toJson();
//        }
//        return $ret;
//    }
//
//    public static function fromJsonList($jsonList)
//    {
//        $ret = [];
//        foreach ($jsonList as $jsonObj) {
//            $room = new QueryRoomModel();
//            $room->fromJson($jsonObj);
//            $ret[] = $room;
//        }
//        return $ret;
//    }
//}