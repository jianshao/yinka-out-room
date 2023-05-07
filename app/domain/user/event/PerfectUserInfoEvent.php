<?php

namespace app\domain\user\event;

use app\domain\events\DomainEvent;
use app\domain\user\dao\UserModelDao;

class PerfectUserInfoEvent extends DomainEvent
{
    public $userModel = null;

    public function __construct($userModel = null, $timestamp = 0)
    {
        parent::__construct($timestamp);
        $this->userModel = $userModel;
    }

    public function jsonToModel($data)
    {
        $userModelStr = $data['user_model'] ?? "";
        if (!empty($userModelStr)) {
            $this->userModel = UserModelDao::getInstance()->dataToModel(json_decode($userModelStr, true));
        }
        $this->timestamp = $data['timestamp'] ?? 0;
    }

    public function modelToJson()
    {
        return [
            'user_model' => json_encode(UserModelDao::getInstance()->modelToData($this->userModel)),
            'timestamp' => $this->timestamp,
        ];
    }
}