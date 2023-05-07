<?php


namespace app\domain\bi;


class BIUserAssetModel
{
    public $eventId = 0;
    // 用户id
    public $uid = 0;
    // 接收用户ID
    public $toUid = 0;
    // 房间id
    public $roomId = 0;
    // 资产类型
    public $type = 0;
    // 资产id
    public $assetId = '';
    // 资产变化
    public $change = 0;
    // 资产变化前
    public $changeBefore = 0;
    // 资产变化后
    public $changeAfter = 0;
    // 创建时间
    public $createTime = 0;
    // 修改时间
    public $updateTime = 0;
    // 参数1
    public $ext1 = '';
    // 参数2
    public $ext2 = '';
    // 参数3
    public $ext3 = '';
    // 参数4
    public $ext4 = '';
    // 参数5
    public $ext5 = '';

    public $toNickname="";
}