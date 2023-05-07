<?php


namespace app\domain\imresource;


use app\utils\ArrayUtil;

/**
 * @desc 聊天背景图
 * Class ImResourceTypeBackground
 * @package app\domain\imresource
 */
class ImResourceTypeBackground
{
    // 资源ID
    public $id = 0;
    // 资源标题
    public $title = '';
    // 资源图片
    public $image = '';

    public function decodeFromJson($jsonObj)
    {
        $this->id = ArrayUtil::safeGet($jsonObj, 'id', 0);
        $this->title = ArrayUtil::safeGet($jsonObj, 'title', '');
        $this->image = ArrayUtil::safeGet($jsonObj, 'image', '');
    }
}