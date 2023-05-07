<?php


namespace app\domain\activity\qixi;


use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;

class QixiUser
{
    public static $CP_STATUS_REMOVE = 1;
    public static $CP_STATUS_REMOVED = 2;
    public static $apply_expire_time = 86400;
    // 用户ID
    public $userId = 0;
    // cp
    public $cpUserId = 0;
    // cp状态 1-发起解除 2-被解除
    public $cpStatus = 0;
    // 相思值
    public $missingValue = 0;
    // cp申请列表 [CpApply]
    public $applyList = [];
    // cp被申请列表 [CpApply]
    public $appliedList = [];
    // 获取的福袋信息 （福袋礼物id => [福袋数量, 领取次数]）
    public $luckyBag = [];
    public $luckyUpdateTime = 0;


    public function __construct($userId, $timestamp=0) {
        $this->userId = $userId;
        $this->luckyUpdateTime = $timestamp;
    }

    public function adjust($timestamp) {
        if (!TimeUtil::isSameDay($this->luckyUpdateTime, $timestamp)) {
            $this->luckyUpdateTime = $timestamp;
            $this->luckyBag = [];
        }
    }

    public function getApply($appliedUid) {
        foreach ($this->applyList as $apply) {
            if ($apply->appliedUid == $appliedUid){
                return $apply;
            }
        }

        return null;
    }

    public function removeApply($cpApply){
        foreach ($this->applyList as $key => $apply) {
            if ($apply->applyUid == $cpApply->applyUid && $apply->appliedUid == $cpApply->appliedUid) {
                unset($this->applyList[$key]);
                break;
            }
        }
    }

    public function removeApplied($cpApply){
        foreach ($this->appliedList as $key => $apply) {
            if ($apply->applyUid == $cpApply->applyUid && $apply->appliedUid == $cpApply->appliedUid) {
                unset($this->appliedList[$key]);
                break;
            }
        }
    }

    public function fromJson($jsonObj, $timestamp) {
        $this->cpUserId = intval(ArrayUtil::safeGet($jsonObj, 'cpUserId', 0));
        $this->cpStatus = intval(ArrayUtil::safeGet($jsonObj, 'cpStatus', 0));
        $this->missingValue = intval(ArrayUtil::safeGet($jsonObj, 'missingValue', 0));
        $this->luckyUpdateTime = intval(ArrayUtil::safeGet($jsonObj, 'luckyUpdateTime', 0));
        $this->luckyBag = array_key_exists('luckyBag', $jsonObj) ? json_decode($jsonObj['luckyBag'], true) : [];

        $applyList = [];
        $applyListStr = ArrayUtil::safeGet($jsonObj, 'applyList');
        if (!empty($applyListStr)){
            $applyListJson = json_decode($applyListStr, true);
            foreach ($applyListJson as $applyJson) {
                if ($timestamp - $applyJson['applyTime'] > self::$apply_expire_time){
                    continue;
                }
                $apply = new CPApply();
                $apply->applyUid = $applyJson['applyUid'];
                $apply->appliedUid = $applyJson['appliedUid'];
                $apply->applyTime = $applyJson['applyTime'];
                $applyList[] = $apply;
            }
        }

        $appliedList = [];
        $appliedListStr = ArrayUtil::safeGet($jsonObj, 'appliedList');
        if (!empty($appliedListStr)){
            $appliedListJson = json_decode($appliedListStr, true);
            foreach ($appliedListJson as $applyJson) {
                if ($timestamp - $applyJson['applyTime'] > self::$apply_expire_time){
                    continue;
                }

                $apply = new CPApply();
                $apply->applyUid = $applyJson['applyUid'];
                $apply->appliedUid = $applyJson['appliedUid'];
                $apply->applyTime = $applyJson['applyTime'];
                $appliedList[] = $apply;
            }
        }

        $this->applyList = $applyList;
        $this->appliedList = $appliedList;

        $this->adjust($timestamp);

        return $this;
    }


    public function applyToJson() {
        $applyList = [];
        foreach ($this->applyList as $apply){
            $applyList[] = $apply->toJson();
        }

        $appliedList = [];
        foreach ($this->appliedList as $apply){
            $appliedList[] = $apply->toJson();
        }

        return [
            'cpUserId' => $this->cpUserId,
            'cpStatus' => $this->cpStatus,
            'missingValue' => $this->missingValue,
            'applyList' => json_encode($applyList),
            'appliedList' => json_encode($appliedList),
        ];
    }

    public function luckyToJson() {
        return [
            'luckyUpdateTime' => $this->luckyUpdateTime,
            'luckyBag' => json_encode($this->luckyBag),
        ];
    }

    public function toJson() {
        $applyList = [];
        foreach ($this->applyList as $apply){
            $applyList[] = $apply->toJson();
        }

        $appliedList = [];
        foreach ($this->appliedList as $apply){
            $appliedList[] = $apply->toJson();
        }

        return [
            'luckyUpdateTime' => $this->luckyUpdateTime,
            'cpUserId' => $this->cpUserId,
            'cpStatus' => $this->cpStatus,
            'missingValue' => $this->missingValue,
            'applyList' => $applyList,
            'appliedList' => $appliedList,
            'luckyBag' => $this->luckyBag
        ];
    }
}