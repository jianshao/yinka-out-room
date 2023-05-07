<?php

namespace app\domain\recall\model;

// 用户召回活动push配置
class PushRecallConfModel
{
    // 主键
    public $id = 0;
    //触发条件
    public $pushWhen = null;
    // 类型
    public $pushType = "";
    // 模版id
    public $templateIds = [];


    public function modelToData()
    {
        $pushWhenModel = new PushRecallConfPushWhenModel();
        $pushWhenData = $pushWhenModel->modelToData($this->pushWhen);
        $templateIds = json_encode($this->templateIds, true);
        return [
            'id' => $this->id,
            'push_when' => $pushWhenData,
            'push_type' => $this->pushType,
            'template_ids' => $templateIds,
        ];
    }
}


