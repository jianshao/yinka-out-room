<?php


namespace app\domain\imresource;


use app\utils\ArrayUtil;

/**
 * @desc 单个表情包描述
 * Class ImResourceTypeEmotionDesc
 * @package app\domain\imresource
 */
class ImResourceTypeEmotionDesc
{
    // 图片id
    public $emotionId = '';
    // 图片名称
    public $emotionName = '';
    // 图片地址
    public $emotionUrl = '';
    public $width = 0;
    public $height = 0;

    public function decodeFromJson($emotion)
    {
        $this->emotionId = ArrayUtil::safeGet($emotion, 'id', 0);
        $this->emotionName = ArrayUtil::safeGet($emotion, 'name', '');
        $this->emotionUrl = ArrayUtil::safeGet($emotion, 'emotion_url', []);
        $this->width = ArrayUtil::safeGet($emotion, 'width', []);
        $this->height = ArrayUtil::safeGet($emotion, 'height', []);
    }
}