<?php


namespace app\domain\imresource;


use app\utils\ArrayUtil;

/**
 * @desc 一组表情包
 * Class ImResourceTypeEmotion
 * @package app\domain\imresource
 */
class ImResourceTypeEmotion
{
    // 资源ID
    public $id = 0;
    // 资源标题
    public $title = '';
    // 资源列表
    public $emotionList = '';
    // 0:未使用  1:正在使用
    public $status = 0;


    public function decodeFromJson($jsonObj)
    {
        $this->id = ArrayUtil::safeGet($jsonObj, 'id', 0);
        $this->title = ArrayUtil::safeGet($jsonObj, 'title', '');
        $this->status = ArrayUtil::safeGet($jsonObj, 'status', 0);
        $emotionList = ArrayUtil::safeGet($jsonObj, 'emotion_list', []);
        $newEmotionList = [];
        foreach ($emotionList as $emotion) {
            $emotionDesc = new ImResourceTypeEmotionDesc();
            $emotionDesc->decodeFromJson($emotion);
            $newEmotionList[] = $emotionDesc;
        }
        $this->emotionList = $newEmotionList;
    }
}