<?php

namespace app\domain\recall\model;

// 用户召回活动配置的 触发条件模型
class PushRecallConfPushWhenModel
{
    // 最大金额
    public $chargeMax = 0;
    // 最小金额
    public $chargeMin = 0;
    // unixDay 离线的时间节点 单位s
    public $time = 0;

    public function dataToModel($data)
    {
        $this->chargeMax = (int)($data['charge_max'] ?? 0);
        $this->chargeMin = (int)($data['charge_min'] ?? 0);
        $this->time = (int)($data['time'] ?? 0);
    }

    public function modelToData(PushRecallConfPushWhenModel $model){
        return [
            'charge_max'=>$model->chargeMax,
            'charge_min'=>$model->chargeMin,
            'time'=>$model->time,
        ];
    }
}


