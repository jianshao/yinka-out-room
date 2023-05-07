<?php


namespace app\domain\activity\king;


use app\utils\ArrayUtil;

class KingUser
{
    // 用户ID
    public $userId = 0;
    // 福利1是否领取 1-可领取 2-已领取
    public $welfare1Status = 1;
    // 福利2是否领取 1-可领取 2-已领取
    public $welfare2Status = 1;


    public function __construct($userId) {
        $this->userId = $userId;
    }

    public function fromJson($jsonObj) {
        $this->welfare1Status = ArrayUtil::safeGet($jsonObj, 'welfare1Status', 1);
        $this->welfare2Status = ArrayUtil::safeGet($jsonObj, 'welfare2Status', 1);
        return $this;
    }

    public function toJson() {
        return [
            'welfare1Status' => $this->welfare1Status,
            'welfare2Status' => $this->welfare2Status,
        ];
    }
}