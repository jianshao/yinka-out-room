<?php


namespace app\domain\activity\qixi;


use app\utils\ArrayUtil;
use app\utils\TimeUtil;
use think\facade\Log;

class CPApply
{
    // 申请人id
    public $applyUid = 0;
    // 被申请人id
    public $appliedUid = 0;
    public $applyTime = 0;

    public function toJson(){
        return [
            'applyUid' => $this->applyUid,
            'appliedUid' => $this->appliedUid,
            'applyTime' => $this->applyTime,
        ];
    }
}