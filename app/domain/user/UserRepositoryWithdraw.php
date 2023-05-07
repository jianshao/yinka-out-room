<?php

namespace app\domain\user;

use app\domain\user\dao\UserModelDao;
use app\service\TokenService;

class UserRepositoryWithdraw
{
    protected static $instance;

    //单例
    public static function getInstance() {
        if (!isset(self::$instance)) {
            self::$instance = new UserRepositoryWithdraw();
        }
        return self::$instance;
    }

    private function newUser($userModel) {
        $user = new UserWithdraw($userModel);
        $token = TokenService::getInstance()->getTokenByUserId($user->getUserId());
        $user->setToken($token);
        return $user;
    }

    /**
     * 创建用户
     *
     * @param $userModel
     */
    public function createUser($userModel) {
        UserModelDao::getInstance()->save();
        return $this->newUser($userModel);
    }

    /**
     * 加载用户
     *
     * @param userId: 要加载的用户ID
     *
     * @return: 如果用户存在返回User, 没找到返回null
     */
    public function loadUser($userId) {
        $model = UserModelDao::getInstance()->loadUserModelWithLock($userId);
        if ($model == null) {
            return null;
        }
        return $this->newUser($model);
    }
}


