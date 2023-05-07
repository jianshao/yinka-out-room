<?php


namespace app\api\controller\open;

use app\common\RedisCommon;
use app\domain\activity\recallUser\RecallUserService;
use app\domain\exceptions\FQException;
use app\domain\guild\dao\MemberGuildModelDao;
use app\domain\guild\dao\MemberSocityModelDao;
use app\domain\user\dao\AccountMapDao;
use app\domain\user\dao\BeanModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\service\UserInfoService;
use app\domain\user\UserRepository;
use app\query\user\elastic\UserModelElasticDao;
use app\query\user\QueryUserService;
use app\utils\TimeUtil;
use think\facade\Request;

class ChaChaLoginCheckController
{
    protected $bad_idfa_map = [
        '00000000-0000-0000-0000-000000000000',
    ];

    protected $bad_deviceId_map = [

    ];



    /**
     * @throws \app\domain\exceptions\FQException
     */
    public function checkUser() {
        $params = Request::param('data', []);
        $registerTime = $params['registerTime'] ?? 0;
        $lastLoginTime = $params['lastLoginTime'] ?? 0;
        $ccUserId = $params['userId'];
        //白名单检测
        if (!$this->checkWhiteList($ccUserId)) {
            $userIds = $this->matchUserList($params);
            $userId = current($userIds); //第一阶段是否匹配到用户
            if ($userId) {
                $userArr = explode('-', $userId);
                $userId = $userArr[0];
                $reason = $userArr[1];
            } else {
                $userId = 0;
                $reason = '';
            }
            list($flag, $isCharge) = $this->checkUserCanLogin($userId, $registerTime, $lastLoginTime);
            if ($flag == false) {
                $redis = RedisCommon::getInstance()->getRedis();
                $redis->hSet('YLCCCheckLogin', $ccUserId, json_encode(['ylUserId' => $userId, 'isCharge' => $isCharge, 'ccUserId' => $ccUserId, 'reason' => $reason, 'ccData' => $params]));
            }
            return rjson(['flag' => $flag]);
        }
        return rjson(['flag' => true]);
    }

    /*根据匹配出的用户信息判断是否可以在茶茶登录*/
    public function checkUserCanLogin($userId, $registerTime, $lastLoginTime)
    {
        $flag = true;
        $isCharge = false;
        if ($userId) {
            $userModel = QueryUserService::getInstance()->queryUserInfo($userId, $userId, 0);
            //第二阶段音恋注册时间早于茶茶
            if ($userModel->registerTime < $registerTime) {
                //第三阶段是否有过充值代充行为
                if (RecallUserService::getInstance()->isHaveChargeAction($userId, ['start_time' => $lastLoginTime - (86400 * 30)])) {
                    $redis = RedisCommon::getInstance()->getRedis();
                    $redis->zAdd('YLCCRichUserIds', time(), $userId);
                    //第四阶段判断是否有工会
                    $isGuildMember = MemberSocityModelDao::getInstance()->getGuidIdByUserId($userId);
                    $isGuild = MemberGuildModelDao::getInstance()->getOneObject(['user_id' => $userId, 'status' => 1]);
                    if (!empty($isGuildMember) || !empty($isGuild)) {
                        $redis = RedisCommon::getInstance()->getRedis();
                        $redis->zAdd('YLCCAnchorOrGuild', time(), $userId);
                        $flag = false;
                    } else {
                        //第六阶段茶茶登录时间与音恋登录时间差小于40天
                        $user = UserRepository::getInstance()->loadUser($userId);
                        if ($user == null) {
                            throw new FQException('用户不存在', 500);
                        }
                        $YlLastLoginTime = $user->getUserModel()->loginTime;
                        $diffDays = TimeUtil::calcDays($lastLoginTime, $YlLastLoginTime);
                        if ($diffDays < 20) {
                            $redis = RedisCommon::getInstance()->getRedis();
                            $redis->zAdd('YLCCNoGuildAndCharge', time(), $userId);
                            $flag = false;
                        }
                    }
                    $isCharge = true;
                } else {
                    //第五阶段茶茶登录时间与音恋登录时间差小于40天
                    $user = UserRepository::getInstance()->loadUser($userId);
                    if ($user == null) {
                        throw new FQException('用户不存在', 500);
                    }
                    $YlLastLoginTime = $user->getUserModel()->loginTime;
                    $diffDays = TimeUtil::calcDays($lastLoginTime, $YlLastLoginTime);
                    if ($diffDays < 20) {
                        $redis = RedisCommon::getInstance()->getRedis();
                        $redis->zAdd('YLCCNoChargeAndGuild', time(), $userId);
                        $flag = false;
                    }
                }
            }
        }
        return [$flag, $isCharge];
    }

    public function matchUserList($params) {
        $userList = [];
        if (isset($params['mobile']) && !empty($params['mobile'])) {
            $userId = AccountMapDao::getInstance()->getUserIdByMobile($params['mobile']);
            if ($userId) {
                $userList[] = $userId . '-mobile';
            }
        }
        //这些搜索功无法在数据库搜索
        if (isset($params['idfa']) && !empty($params['idfa']) && !in_array($params['idfa'], $this->bad_idfa_map)) {
            $userModels = UserModelElasticDao::getInstance()->searchUserByIdfa($params['idfa'], 1);
            if (!empty($userModels)) {
                $userModel = current($userModels);
                $userList[] = $userModel->userId . '-idfa';
            }
        }
        if (isset($params['deviceId']) && !empty($params['deviceId']) && !in_array($params['deviceId'], $this->bad_deviceId_map)) {
            $userModels = UserModelElasticDao::getInstance()->searchUserByDieviceId($params['deviceId'], 1);
            if (!empty($userModels)) {
                $userModel = current($userModels);
                $userList[] = $userModel->userId . '-deviceId';
            }
        }
        return $userList;
    }

    public function checkWhiteList($userId) {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->zScore('YLCCWhiteList', $userId);
    }
}