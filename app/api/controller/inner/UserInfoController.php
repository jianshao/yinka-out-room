<?php


namespace app\api\controller\inner;


use app\Base2Controller;
use app\domain\duke\service\DukeService;
use app\domain\exceptions\FQException;
use app\domain\forum\service\ForumService;
use app\domain\gift\GiftSystem;
use app\domain\user\model\ComplaintUserStatus;
use app\domain\user\model\MemberDetailAuditActionModel;
use app\domain\user\service\UserReportService;
use app\domain\user\service\UserService;
use app\domain\user\UserRepository;
use app\form\ClientInfo;
use app\query\dao\GiftModelDao;
use app\query\prop\service\PropQueryService;
use app\query\user\dao\AttentionModelDao;
use app\query\user\dao\MemberDetailAuditDao;
use app\query\user\QueryUserService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\Error;
use app\view\UserView;
use think\facade\Request;

class UserInfoController extends Base2Controller
{


    public function userInfoForRoom()
    {
        $userId = $this->request->param('userId');
        $userModel = QueryUserService::getInstance()->queryUserInfo($userId, $userId, 0);
        if (empty($userModel)) {
            return rjson('用户不存在', 500);
        }

        $curTime = time();
        $props = PropQueryService::getInstance()->queryUserProps($userId);
        $userProps = [];
        $waredProps = [];
        foreach ($props as $prop) {
            if (!$prop->isDied($curTime) and $prop->balance($curTime) > 0) {
                $userProps[] = [
                    'kindId' => intval($prop->kind->kindId),
                    'propId' => $prop->propId,
                    'isWore' => $prop->isWore
                ];
                if ($prop->isWore) {
                    $waredProps[] = [
                        'propId' => $prop->propId,
                        'kindId' => intval($prop->kind->kindId),
                        'type' => $prop->kind->getTypeName(),
                        'imageIos' => CommonUtil::buildImageUrl($prop->kind->image),
                        'imageAndroid' => CommonUtil::buildImageUrl($prop->kind->imageAndroid),
                        'animation' => CommonUtil::buildImageUrl($prop->kind->animation),
                        'color' => $prop->kind->color,
                        'multiple' => $prop->kind->multiple,
                    ];
                }
            }
        }
        $nicknameModel = MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($userId, MemberDetailAuditActionModel::$nickname);
        $avatarModel = MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($userId, MemberDetailAuditActionModel::$avatar);

        return rjson([
            'profile' => [
                'username' => CommonUtil::filterMobile($userModel->username),
                'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                'password' => $userModel->password,
                'nickname' => $userModel->nickname,
                'sex' => (int)$userModel->sex,
                'vipLevel' => (int)$userModel->vipLevel,
                'role' => $userModel->role,
                'level' => (int)$userModel->lvDengji,
                'prettyId' => (int)$userModel->prettyId,
                'prettyAvatar' => CommonUtil::buildImageUrl($userModel->prettyAvatar),
                'prettyAvatarSvga' => CommonUtil::buildImageUrl($userModel->prettyAvatarSvga),
                'dukeId' => (int)$userModel->dukeLevel,
                'attestation' => (int)$userModel->attestation,
            ],
            'auditProfile' => [
                'nickname' => $nicknameModel->content ? $nicknameModel->content : $userModel->nickname,
                'avatar' => $avatarModel->content ? $avatarModel->content : $userModel->avatar,
            ],
            'props' => $userProps,
            'waredProps' => $waredProps
        ]);
    }

    public function queryAttention()
    {
        $userId = $this->request->param('userId');
        $userIdEd = $this->request->param('userIdEd');
        $attention = AttentionModelDao::getInstance()->loadAttention($userId, $userIdEd);
        if (empty($attention)) {
            return rjson('关系不存在', 500);
        }
        return rjson([
            'userId' => $attention->userId,
            'attentionId' => $attention->attentionId,
            'createTime' => $attention->createTime
        ]);
    }

    public function giftPackInfo()
    {
        $userId = $this->request->param('userId');
        $giftModels = GiftModelDao::getInstance()->loadAllGiftByUserId($userId);
        $giftMap = [];
        foreach ($giftModels as $giftModel) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftModel->kindId);
            if ($giftKind != null && $giftModel->count > 0) {
                if (ArrayUtil::safeGet($giftMap, $giftKind->kindId) != null) {
                    $giftMap[$giftKind->kindId] += $giftModel->count;
                } else {
                    $giftMap[$giftKind->kindId] = $giftModel->count;
                }
            }
        }
        return rjson([
            'giftMap' => $giftMap
        ]);
    }

    /**
     * 虚拟手机号注册
     */
    public function virtualPhoneRegister()
    {
        $params = Request::param();
        $clientInfo = new ClientInfo();
        $clientInfo->fromRequest($this->request);
        try {
            $userId = UserService::getInstance()->registerByVirtualPhone($params['phone'], $clientInfo);
            $user = UserRepository::getInstance()->loadUser($userId);
            return rjson(['info' => UserView::viewUser($user, $params['source'], $this->version, $this->channel)]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }


    /**
     * 用户在线心跳
     */
    public function userOnlineHeartBeat()
    {
        $userId = \think\facade\Request::param('uid');
        if (!$userId) {
            return rjson([], 500);
        }
        $heartInterval = config('config.heartInterval');
        UserService::getInstance()->updateUserOnlineTime($userId, $heartInterval);
        return rjson();
    }


    /**
     * 后台封号操作状态通知：
     * @param int uid 封禁的用户userId
     * @param int status  状态 0解封 1 封禁
     */
    public function blockUserNotice()
    {
        $userId = \think\facade\Request::param('userId', 0, 'intval');
        $status = \think\facade\Request::param('status', 0, 'intval');
        if (empty($userId)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }

//        修改封禁用户的朋友圈信息
        $result = ForumService::getInstance()->forumUpdateForBlockUser($userId, $status);
        return rjson(['row' => $result], 200, 'success');
    }

    //变更投诉状态
    public function complaintUserChange()
    {
        $cid = Request::param("cid", 0, 'intval');
        $adminId = Request::param("adminId", 0);
        $status = Request::param("status", ComplaintUserStatus::$YIWANJIE);
        if ($cid === 0 || $adminId === 0) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }

        $updateRe = UserReportService::getInstance()->complaintUserChange($cid, $adminId, $status);
        if (empty($updateRe)) {
            throw new FQException("操作失败，已经审核过了", 500);
        }
        return rjson([], 200, 'success');
    }


    //投诉用户的跟进
    public function complaintUserFollow()
    {
        $cid = Request::param("cid", 0, 'intval');
        $content = Request::param("content", "");
        $adminId = Request::param("adminId", 0);
        if ($cid === 0 || $content === "" || $adminId === 0) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }

        UserReportService::getInstance()->complaintUserFollow($cid, $content, $adminId);
        return rjson([], 200, 'success');
    }

    //修改用户信息
    public function perfectUserInfo()
    {
        $this->checkAuthInner();
        $userId = intval(Request::param('userId'));
        $datas = Request::param('datas');

        if (empty($datas)) {
            return rjson([], 500, '参数为空');
        }

        try {
            $datas = json_decode($datas, true);
            UserService::getInstance()->innerSetUserInfo($userId, $datas);
            return rjson($msg = "修改成功");
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }


    /**
     * @info 添加隐身用户，后台转入的接口，
     *  python使用该逻辑 可以进所有房间，锁房也可以进入
     * @throws FQException
     */
    public function addYsUser()
    {
        $this->checkAuthInner();
        $userId = Request::param('userId', 0, 'intval'); //用户ID
        if ($userId === 0) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = UserService::getInstance()->innerAddYsUser($userId);
        if (empty($result)) {
            return rjson([], 500, "操作失败");
        }
        return rjson([], 200, 'success');
    }

    /**
     * @Info 删除隐身用户,后台转入
     * @return \think\response\Json
     * @throws FQException
     */
    public function delYsUser()
    {
        $this->checkAuthInner();
        $userId = Request::param('userId', 0, 'intval'); //用户ID
        if ($userId === 0) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = UserService::getInstance()->innerDelYsUser($userId);
        if (empty($result)) {
            return rjson([], 500, "操作失败");
        }
        return rjson([], 200, 'success');
    }

//    添加虚拟用户
    public function addUser()
    {
        $this->checkAuthInner();
        $paramUsername = Request::param('username', ''); //手机号
        $password = Request::param('password', '', 'md5'); //密码
        $sex = Request::param('sex', 0, 'intval'); //性别
        if (empty($paramUsername) || empty($password)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $username = '12' . $paramUsername;
        $result = UserService::getInstance()->innerAddUser($username, $password, $sex);
        if (empty($result)) {
            throw new FQException("修改失败", 500);
        }
        return rjson([], 200, '操作成功');
    }

//    修改用户邀请码
    public function updateUserInvitcode()
    {
        $this->checkAuthInner();
        $invitcode = Request::param('invitcode', 0, 'intval'); //邀请码
        $uid = Request::param("user_id", 0, 'intval');
        if ($invitcode === 0 || $uid === 0) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }

        $result = UserService::getInstance()->innerUpdateUserInvitcode($uid, $invitcode);
        if (!$result) {
            throw new FQException("修改失败", 500);
        }
        return rjson([], 200, '操作成功');
    }

    public function dukeMemberAdd()
    {
        $this->checkAuthInner();
        $dukeId = Request::param('duke_id', 0, 'intval');
        $userId = Request::param('user_id', 0, 'intval');
        $dukeExpires = Request::param('duke_expires', '', "strtotime");

        if (empty($dukeExpires) || empty($userId) || empty($dukeId)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }

        $timestamp = time();
        $result = DukeService::getInstance()->dukeChangeForLevelId($userId, $dukeId, $dukeExpires, $timestamp);
        if (!$result) {
            throw new FQException("操作失败", 500);
        }

        return rjson([], 200, '操作成功');
    }

    //初始化用户认证状态
    public function resetAttention()
    {
        $this->checkAuthInner();
        $userId = Request::param('userId', 0, 'intval');
        if (empty($userId)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = UserService::getInstance()->resetAttention($userId);
        if (empty($result)) {
            throw new FQException("修改失败", 500);
        }
        return rjson([], 200, '操作成功');
    }

}