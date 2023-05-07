<?php

namespace app\web\controller\v1;

use app\query\user\QueryUserService;
use app\web\common\WebBaseController;

class UserController extends WebBaseController
{

    //根据用户账号id获取用户头像和昵称
    public function userData() {
        $token = $this->getToken();
        $userInfo = $this->parseToken($token);
        $uid  =  $userInfo['id'] ?? 0;

        $userModel = QueryUserService::getInstance()->searchUser($uid);
        if($userModel) {
            $data['uid'] = $uid;
            $data['nickname'] = $userModel->nickname;
            $data['avatar'] = getavatar($userModel->avatar);
            return rjson($data,200,'success');
        }
        return rjson([],500,'error');
    }

}
