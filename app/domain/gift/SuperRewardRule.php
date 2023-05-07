<?php


namespace app\domain\gift;


use app\domain\exceptions\FQException;
use app\utils\ArrayUtil;
use think\facade\Log;

class SuperRewardRule
{
    public $limit = 0;
    public $randValues = 0;

    public function decodeFromJson($jsonObj) {
        $randValues = ArrayUtil::safeGet($jsonObj, "randValues", 0);
        if ($randValues < 1) {
            Log::error(sprintf('SuperRewardRule: decodeFromJson error %s', json_encode($jsonObj)));
            throw new FQException('配置错误');
        }
        $this->randValues = $randValues;
        $limit = ArrayUtil::safeGet($jsonObj, "limit", 0);
        $this->limit = $limit;
        return $this;
    }
}