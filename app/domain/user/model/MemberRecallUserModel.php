<?php

namespace app\domain\user\model;

class MemberRecallUserModel
{
    // 用户ID
    public $userId = 0;
    // 用户名
    public $username = '';
    // 昵称
    public $nickname = '';
    // 登录时间
    public $loginTime = 0;
//    虚拟币消费
    public $freecoin = 0;
//    虚拟币总收入
    public $totalcoin = 0;
    //包名
    public $amount = 0;

    public function balance()
    {
        $model = new BeanModel($this->totalcoin, $this->freecoin);
        return $model->balance();
    }
}


