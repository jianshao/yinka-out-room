<?php


namespace app\api\view\v1;


use app\domain\prop\PropKindAvatar;
use app\domain\user\model\AvatarLibraryModel;
use app\domain\user\model\MemberDetailAuditActionModel;
use app\domain\user\service\UserService;
use app\query\prop\service\PropQueryService;
use app\query\user\dao\DaiChongModelDao;
use app\query\user\dao\MemberDetailAuditDao;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\TimeUtil;


//json key:userInfo
class UserView
{

    public static function viewOnlineUser($roomId, $userModel, $roomTypeModel)
    {
        return [
            'userId' => $userModel->userId,
            'nickName' => $userModel->nickname ?: '未知',
            'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
            'voiceIntro' => CommonUtil::buildImageUrl($userModel->voiceIntro),
            'voiceTime' => $userModel->voiceTime,
            'sex' => isset($userModel->sex) ? intval($userModel->sex) : 2,
            'age' => $userModel->birthday ? TimeUtil::birthdayToAge($userModel->birthday) : 18,
            'intro' => $userModel->intro ?: '你主动我们就有故事',
            'roomId' => empty($roomId) ? '' : $roomId,
            'city' => $userModel->city == '' ? '' : $userModel->city,
            'roomModeName' => $roomTypeModel == null ? '' : $roomTypeModel->roomMode,
            'roomModeTabIcon' => $roomTypeModel == null ? '' : CommonUtil::buildImageUrl($roomTypeModel->tabIcon)
        ];
    }


    public static function viewUserInfo($userId, $queryUserModel, $queryUserId, $version, $channel)
    {
        $prop = PropQueryService::getInstance()->getWaredProp($queryUserModel->userId, PropKindAvatar::$TYPE_NAME);
        $avatarMultiple = 0;
        $queryUserModel->prettyAvatar = '';
        $queryUserModel->prettyAvatarSvga = '';
        if ($prop != null) {
            $queryUserModel->prettyAvatar = $prop->kind->image;
            $queryUserModel->prettyAvatarSvga = $prop->kind->animation;
            $avatarMultiple = floatval($prop->kind->multiple);
        }

        $realMobile = CommonUtil::filterMobile($queryUserModel->username);
//        如果是看自己，取最新数据
        if ($userId === (int)$queryUserId) {
            $nicknameModel = MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($queryUserId, MemberDetailAuditActionModel::$nickname);
            $queryUserModel->nickname = $nicknameModel->content ? $nicknameModel->content : $queryUserModel->nickname;
            //api更改用户头像为默认头像的时候，目前逻辑无法处理
            if ($queryUserModel->avatar != 'Public/Uploads/image/male.png' && $queryUserModel->avatar != 'Public/Uploads/image/female.png') {
                $avatarModel = MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($queryUserId, MemberDetailAuditActionModel::$avatar);
                $queryUserModel->avatar = $avatarModel->content ? $avatarModel->content : $queryUserModel->avatar;
            }
            $introModel = MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($queryUserId, MemberDetailAuditActionModel::$intro);
            $queryUserModel->intro = $introModel->content ? $introModel->content : $queryUserModel->intro;
            $voiceModel = MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($queryUserId, MemberDetailAuditActionModel::$voice);
            $voice = UserService::getInstance()->formatContentToVoice($voiceModel->content);
            $queryUserModel->voiceIntro = ArrayUtil::safeGet($voice, 'voiceIntro', '') ?: $queryUserModel->voiceIntro;
            $queryUserModel->voiceTime = ArrayUtil::safeGet($voice, 'voiceTime', '') ?: $queryUserModel->voiceTime;
//            看自己则返回自己的真实手机号
            $realMobile = $queryUserModel->username;
        }

        // 需要审核数据状态
        $auditActions = UserService::getInstance()->getAuditActions($queryUserId);

        $userInfo = [
            'user_id' => $queryUserModel->userId,
            'age' => isset($queryUserModel->birthday) ? TimeUtil::birthdayToAge($queryUserModel->birthday) : 0,
            'pretty_id' => $queryUserModel->prettyId,
            'accid' => $queryUserModel->accId,
            'nickname' => $queryUserModel->nickname,
            'voiceIntro' => CommonUtil::buildImageUrl($queryUserModel->voiceIntro),
            'voiceTime' => (int)$queryUserModel->voiceTime,
            'avatar' => CommonUtil::buildImageUrl($queryUserModel->avatar),
            'prettyAvatar' => CommonUtil::buildImageUrl($queryUserModel->prettyAvatar),
            'prettyAvatarSvga' => CommonUtil::buildImageUrl($queryUserModel->prettyAvatarSvga),
            'attireMultiple' => $avatarMultiple,
            'sex' => $queryUserModel->sex,
            'intro' => $queryUserModel->intro,
            'lv_dengji' => $queryUserModel->lvDengji,
            'birthday' => date('m-d', strtotime($queryUserModel->birthday)),
            'attestation' => $queryUserModel->attestation,
            'city' => $queryUserModel->city,
            'cancel_user_status' => $queryUserModel->cancelStatus,
            'register_time' => date('Y-m-d', $queryUserModel->registerTime),
            'is_vip' => $queryUserModel->vipLevel,
            'push_notice' => $queryUserModel->pushNotice,
            'duke_id' => $queryUserModel->dukeLevel,
            'is_pwd' => empty($queryUserModel->password) ? 0 : 1,
            'mobile' => CommonUtil::filterMobile($queryUserModel->username),
            'realMobile' => $realMobile,
            'twelve_animals' => birthext($queryUserModel->birthday),
            'audit_actions' => $auditActions
        ];


        if ($queryUserModel->vipLevel == 1) {
            $userInfo['upload_num'] = 10;
            $userInfo['vip_exp_time'] = $queryUserModel->vipExpiresTime == 0 ? '' : date('Y-m-d', $queryUserModel->vipExpiresTime);
        } elseif ($queryUserModel->vipLevel == 2) {
            $userInfo['upload_num'] = 10;
            $userInfo['vip_exp_time'] = $queryUserModel->svipExpiresTime == 0 ? '' : date('Y-m-d', $queryUserModel->svipExpiresTime);
        } else {
            $userInfo['upload_num'] = 3;
            $userInfo['vip_exp_time'] = '';
        }
        //获取用户送豆权限
        $sendPermission = DaiChongModelDao::getInstance()->getPermission($userId);
        $userInfo['isShowSendMd'] = empty($sendPermission) ? false : true;
        //获取用户是否可以送自己礼物的权限
        if ($channel == 'appStore' && version_compare($version, '2.9.3', '<=')) {
            $userInfo['sendOneself'] = true;
        } else {
            $userInfo['sendOneself'] = false;
        }
//        $userInfo['sendOneself'] = config('config.sendOneself');
        //获取用户状态
        $userInfo['accountState'] = UserService::getInstance()->getUserStatus($queryUserModel);
        $userInfo['useRN'] = 1;
        return $userInfo;
    }

    /**
     * @param AvatarLibraryModel $model
     * @return string
     */
    public static function randAvatar(AvatarLibraryModel $model)
    {
        return sprintf("%s%s", config("config.APP_URL_image"), $model->href);
    }


}



































