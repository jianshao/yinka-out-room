<?php


namespace app\domain\room\model;


class RoomPhotoModel
{
    public static $STATUS_CHECKING = 1;
    public static $STATUS_CHECK_PASS = 2;
    public static $STATUS_CHECK_FAIL = 3;

    # 图片id
    public $photoId = 0;
    # 上传图片的用户id
    public $userId = 0;
    public $roomId = 0;
    # 图片
    public $image = 0;
    #图片状态 1审核中 2审核成功 3审核失败
    public $status = 0;
    # 解锁需要的礼物id
    public $giftId = 0;
    public $createTime = 0;
}