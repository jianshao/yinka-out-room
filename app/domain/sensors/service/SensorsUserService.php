<?php

namespace app\domain\sensors\service;
use app\domain\dao\MonitoringModelDao;
use app\query\forum\dao\ForumEnjoyModelDao;
use app\query\forum\dao\ForumModelDao;
use app\domain\guild\dao\MemberGuildModelDao;
use app\domain\pay\ProductSystem;
use app\domain\sensors\dao\SensorsUserModelDao;
use app\domain\sensors\model\SensorsUserModel;
use app\domain\user\dao\MemberDetailAuditDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\model\MemberDetailAuditActionModel;
use app\domain\vip\dao\VipModelDao;
use app\query\user\dao\AttentionModelDao;
use app\query\user\dao\FansModelDao;
use app\query\user\dao\FriendModelDao;
use app\utils\TimeUtil;
use think\facade\Log;
use app\domain\exceptions\FQException;


class SensorsUserService
{
    protected $sensorsClass = null;
    protected static $instance;
    protected $sensorsSwitch = false;

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new SensorsUserService();
        }
        return self::$instance;
    }

    /**
     * 参数初始化
     */
    public function __construct()
    {
        require_once("SensorsAnalytics.php");
        $logAgentPath = config('config.sensorsData.log_agent_path');
        $this->sensorsSwitch = config('config.sensorsData.switch');
        $consumer = new FileConsumer(sprintf('%s.%s',$logAgentPath,date('Ymd')));
        $this->sensorsClass = new SensorsAnalytics($consumer);
    }

    /**
     * 导入老用户->录入用户属性->神策
     * @param $userInfo
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function userImportSensors($userInfo)
    {
        $sensorsUserModel = new SensorsUserModel();
        $userId = $userInfo['id'];
        $sensorsUserModel->registerTime = $userInfo['register_time'];
        $sensorsUserModel->channel = $userInfo['register_channel'];
        $sensorsUserModel->sex = SensorsUserModelDao::getInstance()->getSex($userInfo['sex']);
        $sensorsUserModel->birthday = $userInfo['birthday'];
        $sensorsUserModel->nickname = $userInfo['nickname'];
        $sensorsUserModel->constellation = birthextLite($userInfo['birthday']);
        $sensorsUserModel->information = (int)$this->getCompleteScale($userInfo);
        $sensorsUserModel->age = TimeUtil::birthdayToAge($userInfo['birthday']);
        $sensorsUserModel->role = $userInfo['role'] == 1 ? '主播' : '普通用户';
        if($userInfo['guild_id']){
            $guildInfo = MemberGuildModelDao::getInstance()->loadGuildModel('nickname',['id'=>$userInfo['guild_id']]);
            $sensorsUserModel->guild = empty($guildInfo) ? [] : [$guildInfo['nickname']];
        }

        $sensorsUserModel->followNum = AttentionModelDao::getInstance()->getAttentionCount($userId);
        $sensorsUserModel->fansNum = FansModelDao::getInstance()->getFollowCount($userId);
        $sensorsUserModel->friendNum = FriendModelDao::getInstance()->getFriendCount($userId);
        $vipEndTime = $userInfo['is_vip'] == 1 ? $userInfo['vip_exp'] : $userInfo['svip_exp'];
        $vipLevel = '';
        if($userInfo['is_vip'] == 1){
            $vipLevel = 'VIP';
        }else if($userInfo['is_vip'] == 2){
            $vipLevel = 'SVIP';
        }
        $sensorsUserModel->vipLevel = $vipLevel;
        $sensorsUserModel->vipEndTime = $vipEndTime;
        $model = MonitoringModelDao::getInstance()->findByUserId($userId);
        $sensorsUserModel->isOpenTeenagers = empty($model)?false:true;
        $sensorsUserModel->addForumNum = ForumModelDao::getInstance()->getForumCountByWhere(['forum_uid'=>$userId]);
        $sensorsUserModel->likeNum = ForumEnjoyModelDao::getInstance()->getUserEnjoyCount($userId);
        $sensorsUserModel->balance = $userInfo['totalcoin']-$userInfo['freecoin'];
        $sensorsUserModel->geTuiId = $userId;
        $sensorsUserModel->mobile = $userInfo['mobile'];
        $sensorsInfo = SensorsUserModelDao::getInstance()->modelToData($sensorsUserModel);
        $result = $this->sensorsClass->profile_set($userId, true,$sensorsInfo);
        $this->sensorsClass->flush();
        return [$result,$sensorsInfo];
    }

    /**
     * 获取用户资料完成度
     * @throws FQException
     */
    public function getCompleteScale($userInfo)
    {
        $allNum = 6;
        $num = 1;
        if (!empty($userInfo['username'])) {
            $num += 1;
        }
        if (!empty($userInfo['nickname'])) {
            $num += 1;
        }
        if (!empty($userInfo['birthday'])) {
            $num += 1;
        }
        if (!empty($userInfo['city'])) {
            $num += 1;
        }
        if (!empty(MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($userInfo['id'], MemberDetailAuditActionModel::$intro)->content)) {
            $num += 1;
        }
        return sprintf("%.2f", $num/$allNum*100);
    }

    /**
     * 新用户注册->录入用户属性->神策
     * @param $userId
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function userRegisterSensors($userId)
    {
        if(!$this->sensorsSwitch){
            return;
        }
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if($userModel == null){
            Log::error(sprintf('SensorsUserService::userRegisterSensors userId=%s ',$userId));
            return;
        }
        $sensorsUserModel = new SensorsUserModel();
        $sensorsUserModel->registerTime = $userModel->registerTime > 0 ? TimeUtil::timeToStr($userModel->registerTime) : '';
        $sensorsUserModel->channel = $userModel->registerChannel;
        $sensorsUserModel->sex = SensorsUserModelDao::getInstance()->getSex($userModel->sex);
        $sensorsUserModel->birthday = $userModel->birthday;
        $sensorsUserModel->nickname = $userModel->nickname;
        $sensorsUserModel->constellation = birthextLite($userModel->birthday);
        $sensorsUserModel->age = TimeUtil::birthdayToAge($userModel->birthday);
        $sensorsUserModel->geTuiId = $userModel->userId;
        $sensorsUserModel->mobile = $userModel->mobile;
        $sensorsUserModel->userId = $userModel->userId;
        $sensorsUserModel->information = (int)$this->getProfileCompleteScale($userModel);
        $sensorsInfo = SensorsUserModelDao::getInstance()->modelToData($sensorsUserModel);
        $this->sensorsClass->profile_set_once($userId, true,$sensorsInfo);
        $this->sensorsClass->flush();
    }

    /**
     * 修改->用户属性->神策
     * @param $userId
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function editUserSensors($userId)
    {
        return true;
        if(!$this->sensorsSwitch){
            return;
        }
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if($userModel == null){
            Log::error(sprintf('SensorsUserService::editUserSensors userId=%s ',$userId));
            return;
        }
        $sensorsUserModel = new SensorsUserModel();
        $sensorsUserModel->registerTime = $userModel->registerTime > 0 ? TimeUtil::timeToStr($userModel->registerTime) : '';
        $sensorsUserModel->channel = $userModel->registerChannel;
        $sensorsUserModel->sex = SensorsUserModelDao::getInstance()->getSex($userModel->sex);
        $sensorsUserModel->birthday = $userModel->birthday;
        $sensorsUserModel->nickname = $userModel->nickname;
        $sensorsUserModel->constellation = birthextLite($userModel->birthday);
        $sensorsUserModel->age = TimeUtil::birthdayToAge($userModel->birthday);
        $sensorsUserModel->geTuiId = $userModel->userId;
        $sensorsUserModel->mobile = $userModel->mobile;
        $sensorsUserModel->userId = $userModel->userId;
        $sensorsUserModel->information = (int)$this->getProfileCompleteScale($userModel);
        $sensorsInfo = SensorsUserModelDao::getInstance()->modelToData($sensorsUserModel);

        $vipModel = VipModelDao::getInstance()->loadVip($userId);
        $vipEndTime = $vipModel->level == 1 ? $vipModel->vipExpiresTime : $vipModel->svipExpiresTime;
        $vipLevel = '';
        if($vipModel->level == 1){
            $vipLevel = 'VIP';
        }else if($vipModel->level == 2){
            $vipLevel = 'SVIP';
        }
        $sensorsUserModel->vipEndTime = TimeUtil::timeToStr($vipEndTime);
        $sensorsUserModel->vipLevel = $vipLevel;
        $this->sensorsClass->profile_set($userId, true,$sensorsInfo);
        $this->sensorsClass->flush();
    }

    /**
     * 获取用户资料完成度
     * @throws FQException
     */
    public function getProfileCompleteScale($userModel)
    {
        return true;
        $allNum = 6;
        $num = 1;
        if (!empty($userModel->username)) {
            $num += 1;
        }
        if (!empty($userModel->nickname)) {
            $num += 1;
        }
        if (!empty($userModel->birthday)) {
            $num += 1;
        }
        if (!empty($userModel->city)) {
            $num += 1;
        }
        if (!empty(MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($userModel->userId, MemberDetailAuditActionModel::$intro)->content)) {
            $num += 1;
        }
        return sprintf("%.2f", $num/$allNum*100);
    }

    /**
     * 修改用户VIP属性->神策
     * @throws SensorsAnalyticsIllegalDataException
     */
    public function editUserVip($userId,$productId=0)
    {
        return true;
        if(!$this->sensorsSwitch){
            return;
        }
        $vipModel = VipModelDao::getInstance()->loadVip($userId);
        $vipEndTime = $vipModel->level == 1 ? $vipModel->vipExpiresTime : $vipModel->svipExpiresTime;
        $vipLevel = '';
        if($vipModel->level == 1){
            $vipLevel = 'VIP';
        }else if($vipModel->level == 2){
            $vipLevel = 'SVIP';
        }
        $editInfo = [
            'vip_duetime'=>TimeUtil::timeToStr($vipEndTime),
            'vip_level' => $vipLevel,
        ];
        if(!$productId){
            $editInfo['vip_type'] = '';
        }else{
            $product = ProductSystem::getInstance()->findProduct($productId);
            $editInfo['vip_type'] = $product->chargeMsg;
        }
        $this->sensorsClass->profile_set($userId, true,$editInfo);
        $this->sensorsClass->flush();
    }

    /**
     * 修改用户属性->神策
     * @param $userId
     * @param $profile
     */
    public function editUserAttribute($userId,$profile,$source='')
    {
        return true;
        if(!$this->sensorsSwitch){
            return;
        }
        $editInfo = [];
        if ($source == 'admin' && array_key_exists('nickname', $profile)) {
            $editInfo['nick_name'] = (string)$profile['nickname'];
        }
        if (array_key_exists('sex', $profile)) {
            $editInfo['gender'] = SensorsUserModelDao::getInstance()->getSex($profile['sex']);
        }
        if (array_key_exists('birthday', $profile)) {
            $editInfo['birthday'] = $profile['birthday'];
            $editInfo['age'] = TimeUtil::birthdayToAge($profile['birthday']);
            $editInfo['constellation'] = birthextLite($profile['birthday']);
        }
        if (array_key_exists('fansNum', $profile)) {
            $editInfo['fans'] = (int)$profile['fansNum'];
        }
        if (array_key_exists('friendNum', $profile)) {
            $editInfo['friends'] = (int)$profile['friendNum'];
        }
        if (array_key_exists('attentionNum', $profile)) {
            $editInfo['follows'] = (int)$profile['attentionNum'];
        }
        if (array_key_exists('isOpenTeenagers', $profile)) {
            $editInfo['teenager'] = (bool)$profile['isOpenTeenagers'];
        }
        if (array_key_exists('mobile', $profile)) {
            $editInfo['iPhone'] = $profile['mobile'];
        }
        if (array_key_exists('roleType', $profile)) {
            $userModel = UserModelDao::getInstance()->loadUserModel($userId);
            if($userModel->guildId){
                $guildModel = MemberGuildModelDao::getInstance()->loadGuildModelForId($userModel->guildId);
            }else{
                $guildModel = null;
            }
            $editInfo['roletype'] = $guildModel != null ? '主播':'用户';
            $editInfo['group'] = $guildModel != null ? [$guildModel->nickname] : [];
        }
        $this->sensorsClass->profile_set($userId, true,$editInfo);
        $this->sensorsClass->flush();
    }

    /**
     * 更换绑定手机号
     */
    public function setMobile($userId,$mobile)
    {
        return true;
        if(!$this->sensorsSwitch){
            return;
        }
        $this->sensorsClass->profile_set($userId, true,[
            'iPhone' => $mobile
        ]);
        $this->sensorsClass->flush();
    }

    /**
     * 动态点赞->更新用户属性->神策
     */
    public function enjoyForum($userId)
    {
        return true;
        if(!$this->sensorsSwitch){
            return;
        }
        $this->sensorsClass->profile_increment($userId, true,[
            'total_like' => 1
        ]);
        $this->sensorsClass->flush();
    }

    /**
     * 签到->更新用户属性->神策
     */
    public function weekSign($userId)
    {
        return true;
        if(!$this->sensorsSwitch){
            return;
        }
        $this->sensorsClass->profile_increment($userId, true,[
            'total_checkin' => 1
        ]);
        $this->sensorsClass->flush();
    }

    /**
     * 发布动态数->更新用户属性->神策
     */
    public function addForum($userId)
    {
        return true;
        if(!$this->sensorsSwitch){
            return;
        }
        $this->sensorsClass->profile_increment($userId, true,[
            'total_release' => 1
        ]);
        $this->sensorsClass->flush();
    }

    /**
     * 开启青少年模式->更新用户属性->神策
     * @param $extra
     */
    public function switchMonitor($userId,$switch)
    {
        return true;
        if(!$this->sensorsSwitch){
            return;
        }
        $this->editUserAttribute($userId,['isOpenTeenagers'=>$switch]);
    }

    /**
     * 关注用户->更新用户属性->神策
     * @param $extra
     */
    public function careUser($userId,$attentionUserIds)
    {
        return true;
        if(!$this->sensorsSwitch){
            return;
        }
        foreach($attentionUserIds as $toUserId){
            // 修改用户的粉丝数
            $fansNum = FansModelDao::getInstance()->getFollowCount($toUserId);
            $this->sensorsClass->profile_set($toUserId, true,['fans'=>$fansNum]);
        }
        $attentionNum = AttentionModelDao::getInstance()->getAttentionCount($userId);
        $friendNum = FriendModelDao::getInstance()->getFriendCount($userId);
        $this->sensorsClass->profile_set($userId, true,['follows'=>$attentionNum,'friends'=>$friendNum]);
        $this->sensorsClass->flush();
    }

    /**
     * 主播魅力等级变更->更新用户属性->神策
     * @param $extra
     */
//    public function levelChange($userId,)
//    {
//        $userId = $extra['userId'];
//        $toUsersIdList = $extra['toUsersIdList'];
//        foreach($toUsersIdList as $toUserId){
//            // 修改用户的粉丝数
//            $fansNum = AttentionModelDao::getInstance()->getFollowCount($toUserId);
//            $this->editUserAttribute($toUserId,['fansNum'=>$fansNum]);
//        }
//        $attentionNum = AttentionModelDao::getInstance()->getAttentionCount($userId);
//        $this->editUserAttribute($userId,['attentionNum'=>$attentionNum]);
//    }

}