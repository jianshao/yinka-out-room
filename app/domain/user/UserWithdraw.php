<?php

namespace app\domain\user;

use app\domain\asset\UserAssets;
use app\domain\duke\Duke;
use app\domain\task\UserTasks;
use app\domain\vip\Vip;


class UserWithdraw
{
    // UserAssets
    private $assets = null;
    // UserTasks
    private $tasks = null;
    // 数据
    private $userModel = null;
    // 用户token
    private $token = '';
    private $clientInfo = null;

    private $duke = null;
    private $vip = null;

    private $todayEarnings = null;

    public $isRegister = false;
    public $pwdLayer = 0;


    public function __construct($userModel)
    {
        $this->userModel = $userModel;
    }

    public function getAssets()
    {
        if ($this->assets == null) {
            $this->assets = new UserAssets($this);
        }
        return $this->assets;
    }

    public function getTasks()
    {
        if ($this->tasks == null) {
            $this->tasks = new UserTasks($this);
        }
        return $this->tasks;
    }

    public function getDuke($timestamp)
    {
        if ($this->duke == null) {
            $duke = new Duke($this);
            $duke->load($timestamp);
            $this->duke = $duke;
        }
        return $this->duke;
    }

    public function getVip($timestamp)
    {
        if ($this->vip == null) {
            $vip = new Vip($this);
            $vip->load($timestamp);
            $this->vip = $vip;
        }
        return $this->vip;
    }

    public function getTodayEarnings($timestamp)
    {
        if ($this->todayEarnings == null) {
            $todayEarnings = new TodayEarnings($this);
            $todayEarnings->load($timestamp);
            $this->todayEarnings = $todayEarnings;
        }
        return $todayEarnings;
    }

    public function getUserId()
    {
        return $this->userModel->userId;
    }

    public function getUserModel()
    {
        return $this->userModel;
    }

    public function getToken()
    {
        return $this->token;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function setClientInfo($clientInfo)
    {
        $this->clientInfo = $clientInfo;
    }

    public function getClientInfo()
    {
        return $this->clientInfo;
    }

}



