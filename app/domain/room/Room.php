<?php

namespace app\domain\room;

use app\domain\room\dao\RoomModelDao;
use app\domain\room\dao\RoomTypeModelDao;
use think\facade\Log;

class Room
{
    private $model = null;
    private $roomType = null;

    public function __construct($roomModel) {
        $this->model = $roomModel;
    }

    public function getRoomId() {
        return $this->model->roomId;
    }

    public function getModel() {
        return $this->model;
    }

    public function unlockRoom() {
        $this->model->lock = 0;
        $this->model->password = '';
        RoomModelDao::getInstance()->updateDatas($this->model->roomId, [
            'room_password' => '',
            'room_lock' => 0,
        ]);
        Log::info(sprintf('UnlockRoom: roomId=%d', $this->getRoomId()));
    }

    public function lockRoom($password) {
        $this->model->lock = 1;
        $this->model->password = $password;
        RoomModelDao::getInstance()->updateDatas($this->model->roomId, [
            'room_password' => $password,
            'room_lock' => 1,
        ]);
        Log::info(sprintf('LockRoom: roomId=%d password=%s', $this->getRoomId(), $password));
    }

    public function getRoomType() {
        if ($this->roomType == null) {
            $this->roomType = RoomTypeModelDao::getInstance()->loadRoomType($this->model->roomType);
        }
        return $this->roomType;
    }

    public function updateProfile($profile) {
        $updateDatas = [];
        if (array_key_exists('name', $profile)) {
            $updateDatas['room_name'] = $this->model->name = $profile['name'];
        }

        if (array_key_exists('desc', $profile)) {
            $updateDatas['room_desc'] = $this->model->desc = $profile['desc'];
        }

        if (array_key_exists('welcomes', $profile)) {
            $updateDatas['room_welcomes'] = $this->model->welcomes = $profile['welcomes'];
        }

        if (array_key_exists('roomType', $profile)) {
            $updateDatas['room_type'] = $this->model->roomType = $profile['roomType'];
        }

        if (array_key_exists('backgroundImage', $profile)) {
            $updateDatas['background_image'] = $this->model->backgroundImage = $profile['backgroundImage'];
        }

        if (array_key_exists('isWheat', $profile)) {
            $updateDatas['is_wheat'] = $this->model->isWheat = $profile['isWheat'];
        }

        if (!empty($updateDatas)) {
            RoomModelDao::getInstance()->updateDatas($this->getRoomId(), $updateDatas);
            Log::info(sprintf('UpdateProfileOk: roomId=%d profile=%s', $this->getRoomId(), json_encode($updateDatas)));
        }
    }
}