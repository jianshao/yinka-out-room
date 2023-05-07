<?php

namespace app\domain\open\model;

use app\utils\ArrayUtil;

/**
 * 个推回调上报模型
 */
class GetuiCallbackModel
{
    public $code = "";
    public $recvtime = 0;
    public $appid = "";
    public $sign = "";
    public $msgid = "";
    public $actionId = "";
    public $taskid = "";
    public $desc = "";
    public $cid = "";

    public function dataToModel($data)
    {
        $this->code = ArrayUtil::safeGet($data, 'code', "");
        $this->recvtime = ArrayUtil::safeGet($data, 'recvtime', 0);
        $this->appid = ArrayUtil::safeGet($data, 'appid', "");
        $this->sign = ArrayUtil::safeGet($data, 'sign', "");
        $this->msgid = ArrayUtil::safeGet($data, 'msgid', "");
        $this->actionId = ArrayUtil::safeGet($data, 'actionId', "");
        $this->taskid = ArrayUtil::safeGet($data, 'taskid', "");
        $this->desc = ArrayUtil::safeGet($data, 'desc', "");
        $this->cid = ArrayUtil::safeGet($data, 'cid', "");
        return $this;
    }

    public function modelTodata()
    {
        return [
            'code' => $this->code,
            'recvtime' => $this->recvtime,
            'appid' => $this->appid,
            'sign' => $this->sign,
            'msgid' => $this->msgid,
            'actionId' => $this->actionId,
            'taskid' => $this->taskid,
            'desc' => $this->desc,
            'cid' => $this->cid,
        ];
    }
}


