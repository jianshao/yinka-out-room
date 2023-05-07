<?php


namespace app\form;


use app\domain\exceptions\FQException;

class ReceiveUser
{
    public $userId = 0;
    public $micId = 0;

    public function __construct($userId, $micId) {
        $this->userId = $userId;
        $this->micId = $micId;
    }

    public static function fromUserMicIdArray($userIds, $micIds, $selfId = 0) {
        if (in_array($selfId, $userIds)) {
            throw new FQException('不可以送自己礼物哦');
        }
        $ret = [];
        if (count($userIds) != count($micIds)) {
            throw new FQException('用户或者麦位参数错误');
        }
        for ($i = 0; $i < count($userIds); $i++) {
            $ret[] = new ReceiveUser(intval($userIds[$i]), intval($micIds[$i]));
        }
        return $ret;
    }
}


