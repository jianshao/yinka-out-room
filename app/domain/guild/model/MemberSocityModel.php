<?php


namespace app\domain\guild\model;


class MemberSocityModel
{
    //公会id
    public $id = 0;
    public $roomId = 0;

    public $guildId = 0;

    //创建公会用户uid
    public $userId = 0;

    public $socity=0;
    //公会昵称
    public $status = "";

    public $auditTime=0;

    public $refuseTime=0;
    //公会logo
    public $applyQuitTime = 0;
}