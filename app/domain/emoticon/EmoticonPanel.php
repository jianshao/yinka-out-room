<?php

namespace app\domain\emoticon;

use app\utils\ArrayUtil;

class EmoticonPanel
{
    public $name = '';
    public $icon = '';
    public $emoticonIds = null;
    public $emoticons = null;
    public $mold = 0;

    public function decodeFromJson($jsonObj) {
        $this->name = $jsonObj['name'];
        $this->icon = $jsonObj['icon'];
        $this->mold = $jsonObj['mold'];
        $this->emoticonIds = $jsonObj['emoticons'];
    }

    public function initByEmoticonMap($emoticonMap) {
        $this->emoticons = [];
        foreach ($this->emoticonIds as $emoticonId) {
            $emoticon = ArrayUtil::safeGet($emoticonMap, $emoticonId);
            if ($emoticon != null) {
                $this->emoticons[] = $emoticon;
            }
        }
    }
}