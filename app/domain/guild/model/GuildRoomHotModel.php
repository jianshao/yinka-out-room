<?php

namespace app\domain\guild\model;

//公会热度cache hashmodel

class GuildRoomHotModel
{

    public $member = 0;       //用户热度值
    public $orignal = 0;      //手动热度值
    public $gift = 0;         //礼物热度值
    public $chat = 0;         //聊天热度值


    public function setOriginHot($hot)
    {
        $this->orignal = intval($hot);
    }

    public function setMember($hot)
    {
        $this->member = intval($hot);
    }

    public function setGift($hot)
    {
        $this->gift = intval($hot);
    }

    public function setChat($hot)
    {
        $this->chat = intval($hot);
    }


    public function getOriginHot()
    {
        return $this->orignal;
    }

    public function getMemberHot()
    {
        return $this->member;

    }

    public function getGiftHot()
    {
        return $this->gift;
    }

    public function getChatHot()
    {
        return $this->chat;

    }


    /**
     * @param array $data
     */
    public function dataToModel(array $data)
    {
        if (empty($data)) {
            return;
        }
        if (isset($data['member'])) {
            $this->setMember($data['member']);
        }
        if (isset($data['orignal'])) {
            $this->setOriginHot($data['orignal']);
        }
        if (isset($data['gift'])) {
            $this->setGift($data['gift']);
        }
        if (isset($data['chat'])) {
            $this->setChat($data['chat']);
        }
        return;
    }


    public function modelToData()
    {
        return [
            'member' => $this->getMemberHot(),
            'orignal' => $this->getOriginHot(),
            'gift' => $this->getGiftHot(),
            'chat' => $this->getChatHot()
        ];
    }

    public function getSumHot()
    {
        $data = $this->modelToData();
        return array_sum($data);
    }

    public function giftDecCalculate($rate)
    {
        if (empty($rate)) {
            return;
        }
        if ($this->gift <= 0) {
            return;
        }
        $hot=floor($this->gift * (1 - $rate));
        if ($hot<0){
            $hot=0;
        }
        $this->gift = $hot;
    }

    public function chatDecCalculate($rate)
    {
        if (empty($rate)) {
            return;
        }
        if ($this->chat <= 0) {
            return;
        }
        $hot=floor($this->chat * (1 - $rate));
        if ($hot<0){
            $hot=0;
        }
        $this->chat = $hot;
    }

}


