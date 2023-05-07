<?php

namespace app\domain\user\service;

use app\domain\dao\UserIdentityModelDao;
use app\utils\CommonUtil;
use think\facade\Log;

class UnderAgeService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UnderAgeService();
        }
        return self::$instance;
    }

    /**
     * 未满18周岁 禁止操作
     * @param $userId
     * @return bool
     */
    public function underAgeProhibit($userId)
    {
        $startTime = strtotime(date('2022-04-01'));
        # 是否实名认证
        $identityModel = UserIdentityModelDao::getInstance()->loadByWhere([
            'status' => 1,
            'uid'    => $userId
        ]);
        if(empty($identityModel) || $identityModel->createTime < $startTime){
            return false;
        }
        $userAge = CommonUtil::getAge($identityModel->certno);
        if($userAge < 18){
            # 未满18周岁
            Log::info(sprintf('UnderAgeService underAgeProhibit userId=%s certNo=%s userAge=%s',$userId, $identityModel->certno,$userAge));
            return true;
        }else{
            return false;
        }
    }

}