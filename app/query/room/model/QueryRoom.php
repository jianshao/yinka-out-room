<?php


namespace app\query\room\model;


class QueryRoom
{
    public $roomId = 0;
    public $guildId = 0;
    public $prettyRoomId = 0;
    public $roomName = '';
    public $roomType = 0;
    public $roomTypeName = '';
    public $roomMode = '';
    public $lock = 0;
    public $visitorNumber = 0;
    public $visitorExternNumber = 0;
    public $visitorUsers = 0;
    public $isLive = 0;
    public $isHot = 0;
    public $hxRoom = 0;
    public $image = '';
    public $tagImage = '';
    public $tagId = 0;
    public $tabIcon = '';
    public $ownerUserId = 0;
    public $ownerAvatar = '';
    public $ownerNickname = '';
    public $online = 0;
    public $popularNumber = 0;

    public function toJson() {
        return [
            'roomId' => $this->roomId,
            'guildId' => $this->guildId,
            'prettyRoomId' => $this->prettyRoomId,
            'roomName' => $this->roomName,
            'roomType' => $this->roomType,
            'roomTypeName' => $this->roomTypeName,
            'roomMode' => $this->roomMode,
            'lock' => $this->lock,
            'visitorNumber' => $this->visitorNumber,
            'visitorExternNumber' => $this->visitorExternNumber,
            'visitorUsers' => $this->visitorUsers,
            'isLive' => $this->isLive,
            'isHot' => $this->isHot,
            'hxRoom' => $this->hxRoom,
            'image' => $this->image,
            'tagImage' => $this->tagImage,
            'tagId' => $this->tagId,
            'tabIcon' => $this->tabIcon,
            'ownerUserId' => $this->ownerUserId,
            'ownerAvatar' => $this->ownerAvatar,
            'ownerNickname' => $this->ownerNickname,
            'online' => $this->online,
            'popularNumber' => $this->popularNumber,
        ];
    }

    public function fromJson($jsonObj)
    {
        $this->roomId = $jsonObj['roomId'] ?? 0;
        $this->guildId = $jsonObj['guildId'] ?? 0;
        $this->prettyRoomId = $jsonObj['prettyRoomId'] ?? 0;
        $this->roomName = $jsonObj['roomName'] ?? "";
        $this->roomType = $jsonObj['roomType'] ?? 0;
        $this->roomTypeName = $jsonObj['roomTypeName'] ?? "";
        $this->roomMode = $jsonObj['roomMode'] ?? '';
        $this->lock = $jsonObj['lock'] ?? 0;
        $this->visitorNumber = $jsonObj['visitorNumber'] ?? 0;
        $this->visitorExternNumber = $jsonObj['visitorExternNumber'] ?? 0;
        $this->visitorUsers = $jsonObj['visitorUsers'] ?? 0;
        $this->isLive = $jsonObj['isLive'] ?? 0;
        $this->isHot = $jsonObj['isHot'] ?? 0;
        $this->hxRoom = $jsonObj['hxRoom'] ?? 0;
        $this->image = $jsonObj['image'] ?? "";
        $this->tagImage = $jsonObj['tagImage'] ?? "";
        $this->tagId = $jsonObj['tagId'] ?? 0;
        $this->tabIcon = $jsonObj['tabIcon'] ?? "";
        $this->ownerUserId = $jsonObj['ownerUserId'] ?? 0;
        $this->ownerAvatar = $jsonObj['ownerAvatar'] ?? "";
        $this->ownerNickname = $jsonObj['ownerNickname'] ?? "";
        $this->online = $jsonObj['online'] ?? 0;
        $this->popularNumber = $jsonObj['popularNumber'] ?? 0;
        return $this;
    }

    public static function toJsonList($rooms) {
        $ret = [];
        foreach ($rooms as $room) {
            $ret[] = $room->toJson();
        }
        return $ret;
    }

    public static function fromJsonList($jsonList) {
        $ret = [];
        foreach ($jsonList as $jsonObj) {
            $room = new QueryRoom();
            $room->fromJson($jsonObj);
            $ret[] = $room;
        }
        return $ret;
    }
}