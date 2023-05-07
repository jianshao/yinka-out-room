<?php

namespace app\domain\level;

use app\domain\asset\rewardcontent\ContentRegister;
use app\utils\ArrayUtil;

class PrivilegeLevel
{
    //等级
    public $level = 0;
    //等级特权名称
    public $title = '';
    //等级特权图片
    public $image = '';
    //v2特权图片
    public $twoImage = '';
    //等级预览图片
    public $previewImage = '';
    //等级预览文案
    public $content = '';
    //奖励
    public $reward = null;
    //奖励文案
    public $rewardMsg = '';
    //等级奖励类型 avatar-头像框 screen-公屏变色 pretty-靓号
    public $rewardType = '';

    public function decodeFromJson($jsonObj)
    {
        $this->level = $jsonObj["level"];
        $this->title = $jsonObj["title"];
        $this->image = $jsonObj["image"];
        $this->twoImage = ArrayUtil::safeGet($jsonObj, 'twoImage');
        $this->previewImage = $jsonObj["previewImage"];
        $this->content = $jsonObj["content"];

        $this->rewardType = ArrayUtil::safeGet($jsonObj, 'rewardType');
        $this->rewardMsg = ArrayUtil::safeGet($jsonObj, 'rewardMsg');
        if (ArrayUtil::safeGet($jsonObj, 'rewards') != null) {
            $this->reward = ContentRegister::getInstance()->decodeList($jsonObj['rewards']);
        }
    }
}