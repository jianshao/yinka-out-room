<?php


namespace app\domain\guild\service;

use app\core\mysql\Sharding;
use app\domain\dao\UserIdentityModelDao;
use app\domain\exceptions\FQException;
use app\domain\guild\dao\MemberGuildModelDao;
use app\domain\guild\dao\MemberSocityModelDao;
use app\domain\queue\producer\YunXinMsg;
use app\domain\user\dao\DaiChongModelDao;
use app\domain\user\dao\UserModelDao;
use app\event\GhAuditMemberEvent;
use app\event\InnerAuditMemberEvent;
use app\service\VerifyCodeService;
use app\utils\CommonUtil;
use Exception;
use think\Facade\Log;

class GuildService
{
    protected static $instance;

    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new GuildService();
        }
        return self::$instance;
    }

    /**
     * @info 申请加入公会
     * @param $userId
     * @param $guildId
     * @param $phone
     * @param $verifyCode
     * @return array
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function guildAdd($userId, $guildId, $phone, $verifyCode)
    {
        CommonUtil::validateMobile($phone);
        VerifyCodeService::getInstance()->checkVerifyCodeAdapter($phone, $verifyCode);

        if (!is_numeric($guildId)) {
            throw new FQException('公会不正确', 601);
        }

        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if ($userModel == null) {
            throw new FQException('用户不存在', 500);
        }

        if (empty($userModel->username)) {
            throw new Exception('您还没有绑定手机号', 500);
        }

        if ($userModel->attestation != 1) {
            throw new FQException('请进行实名认证', 302);
        }

        $userIdentityModel = UserIdentityModelDao::getInstance()->loadByWhere(['uid' => $userId, 'status' => 1]);
        if (!empty($userIdentityModel) && CommonUtil::getAge($userIdentityModel->certno) < 18) {
            throw new FQException('加入公会失败，您的年龄不满18周岁', 601);
        }

        $where['id'] = $guildId;
        $field = 'id,user_id,nickname';
        $guild_detail = MemberGuildModelDao::getInstance()->getOne($where, $field);

        if (empty($guild_detail)) {
            throw new FQException('当前公会不存在', 601);
        }

        //查询当前公会是否存在
        $iswhere[] = ['status', 'in', [0, 1]];
        $iswhere[] = ['user_id', '=', $userId];
        $isJoin = MemberSocityModelDao::getInstance()->getOnes($iswhere);
        if ($isJoin) {
            throw new FQException('已申请入驻公会', 601);
        }

        // 审请加入公会操作
        $join_data = [
            "guild_id" => $guildId,
            "user_id" => $userId,
            "socity" => '0.7',
            "addtime" => date('Y-m-d H:i:s', time()),
            "status" => 0,
            "audit_time" => 0,
        ];
        MemberSocityModelDao::getInstance()->getModel()->save($join_data);
        $where = [['status', '=', 0]];
        return MemberSocityModelDao::getInstance()->loadMemberGuildModel($where);
    }

    public function cancelGuild($guildId, $id)
    {
        $where[] = ['id', '=', $id];
        $where[] = ['guild_id', '=', $guildId];
        $where[] = ['status', '=', 0];
        $isCancel = MemberSocityModelDao::getInstance()->getOnes($where);
        if ($isCancel) {
            $data['status'] = 3;
            $updateWhere[] = ['id', '=', $id];
            $updateWhere[] = ['guild_id', '=', $guildId];
            $updateWhere[] = ['status', '=', 0];
            $res = MemberSocityModelDao::getInstance()->updateDatas($updateWhere, $data);
            if (!$res) {
                throw new FQException('取消失败', 500);
            }
        } else {
            throw new FQException('取消失败', 500);
        }
    }


    /**
     * @info 搜索公会
     * @param $guildId
     * @return array
     * @throws FQException
     */
    public function searchGuild($guildId)
    {
        $where['id'] = $guildId;
        $field = 'id as guild_id,user_id,nickname,logo_url';
        $guildInfo = MemberGuildModelDao::getInstance()->loadGuildModel($field, $where);
        if (empty($guildInfo)) {
            throw new FQException('当前公会不存在', 500);
        }
        return $guildInfo;
    }

    /**
     * @info 用户的公会
     * @param $userId int 用户uid
     * @return array
     * @throws FQException
     */
    public function getUserGuild($userId)
    {
        $where = [
            ['status', 'in', '0,1'],
            ['user_id', '=', $userId]
        ];
        $socityData = MemberSocityModelDao::getInstance()->getOne($where);
        $guildList = [];
        if (!empty($socityData)) {
            $guild_detail = MemberGuildModelDao::getInstance()->getOneObject(['id' => $socityData['guild_id']]);
            if (!empty($guild_detail)) {
                $guildList[] = [
                    'id' => $socityData['id'],
                    'guild_id' => $socityData['guild_id'],
                    'status' => $socityData['status'],
                    'addtime' => $socityData['addtime'],
                    'apply_quit_time' => $socityData['apply_quit_time'],
                    'nickname' => $guild_detail['nickname'],
                    'logo_url' => $guild_detail['logo_url'],
                ];
            }
        }

        return $guildList;
    }


    /**
     * @info 用户与公会的关系 -1 没有关系 0 待审核 1 公会成员 2 已申请退出公会
     * @param $guildId
     * @param $userId
     * @return int|mixed
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function getUserGuildStatus($guildId, $userId)
    {
        $where = [
            ['guild_id', '=', $guildId],
            ['user_id', '=', $userId],
            ['status', 'in', '0,1'],
        ];
        $userGuildInfo = MemberSocityModelDao::getInstance()->loadMemberGuildModel($where);
        if (!empty($userGuildInfo)) {
            // 已申请退出公会申请
            if ($userGuildInfo['status'] == 1 && $userGuildInfo['apply_quit_time'] > 0) {
                $userGuildStatus = 2;
            } else {
                $userGuildStatus = (int)$userGuildInfo['status'];
            }
            $id = $userGuildInfo['id'];
        } else {
            $userGuildStatus = -1;
            $id = 0;
        }
        return [$userGuildStatus, $id];
    }

    /**
     * @info 取消申请公会
     * @param $guildId
     * @param $userGuildId
     * @param $userId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cancelApplyGuild($guildId, $userGuildId, $userId)
    {
        $where = [
            ['id', '=', $userGuildId],
            ['guild_id', '=', $guildId],
            ['status', '=', 0],
        ];
        $userGuildInfo = MemberSocityModelDao::getInstance()->loadMemberGuildModel($where);
        if (empty($userGuildInfo) || $userGuildInfo['user_id'] != $userId) {
            return [false, '不符合条件'];
        }
        $upData = [
            'status' => 3
        ];
        $isOk = MemberSocityModelDao::getInstance()->updateDatas($where, $upData);
        if ($isOk) {
            return [true, '取消成功'];
        } else {
            return [false, '退出失败'];
        }
    }

    /**
     * @info 申请退出公会
     * @param $guildId
     * @param $userGuildId
     * @param $userId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function applyQuitGuild($guildId, $userGuildId, $userId)
    {
        $where = [
            ['id', '=', $userGuildId],
            ['guild_id', '=', $guildId],
            ['status', '=', 1]
        ];
        $userGuildInfo = MemberSocityModelDao::getInstance()->loadMemberGuildModel($where);
        if (empty($userGuildInfo) || $userGuildInfo['user_id'] != $userId) {
            return [false, '不是公会成员'];
        }
        if ($userGuildInfo['apply_quit_time'] > 0) {
            return [false, '您已提交申请退出公会'];
        }
        $upData = [
            'apply_quit_time' => time()
        ];
        $isOk = MemberSocityModelDao::getInstance()->updateDatas($where, $upData);
        if ($isOk) {
            return [true, '申请退出公会成功'];
        } else {
            return [false, '申请退出公会失败'];
        }
    }

    /**
     * @info 取消申请退出公会
     * @param $guildId
     * @param $userGuildId
     * @param $userId
     * @return array
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function cancelQuitGuild($guildId, $userGuildId, $userId)
    {
        $where[] = [
            ['id', '=', $userGuildId],
            ['guild_id', '=', $guildId],
            ['status', '=', 1]
        ];
        $userGuildInfo = MemberSocityModelDao::getInstance()->loadMemberGuildModel($where);
        if (empty($userGuildInfo) || $userGuildInfo['user_id'] != $userId) {
            return [false, '不是公会成员'];
        }
        if (!$userGuildInfo['apply_quit_time']) {
            return [false, '您未提交退出公会申请'];
        }
        $upData = [
            'apply_quit_time' => 0
        ];
        $isOk = MemberSocityModelDao::getInstance()->updateDatas($where, $upData);
        if ($isOk) {
            return [true, '取消退出公会成功'];
        } else {
            return [false, '取消退出公会失败'];
        }
    }


    /**
     * 后台创建公会
     * @param $userId
     * @param $nickname
     * @param $password
     * @param $proportionally
     */
    public function createGuild($userId, $nickname, $password, $proportionally)
    {
        if (MemberGuildModelDao::getInstance()->loadGuildModel('id', ['nickname' => $nickname])) {
            throw new FQException('工会名称已存在', 500);
        }
        if (MemberGuildModelDao::getInstance()->loadGuildModel('id', ['user_id' => $userId])) {
            throw new FQException('此用户创建过工会', 500);
        }
        if ($proportionally >= 100) {
            throw new FQException('公会分成比例不能超过百分比', 500);
        }
        $userInfo = UserModelDao::getInstance()->findPrettyIdLoadUserModel($userId, 'id,username');
        if (empty($userInfo)) {
            throw new FQException('用户不存在', 500);
        }
        if ($userInfo['id'] != $userInfo['pretty_id']) {
            $userId = $userInfo['id'];
        }
        if (MemberGuildModelDao::getInstance()->findUserGuild($userId, $userInfo['username'])) {
            throw new FQException('用户已创建了其他公会', 500);
        }
        $guildMember = MemberSocityModelDao::getInstance()->loadMemberGuildModel([['user_id', '=', $userId], ['status', '=', 1]]);
        $userSubstitutePay = DaiChongModelDao::getInstance()->getUserSubstitutePay($userId);
        if ($guildMember && $userSubstitutePay) {
            throw new FQException('用户已有公会', 500);
        }
        $data = [
            'nickname' => $nickname,
            "phone" => $userInfo['username'],
            "user_id" => $userId,
            "password" => md5($password),
            'proportionally' => $proportionally / 100,
        ];
        try {
            $guildId = Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function () use ($data) {
                $guildId = MemberGuildModelDao::getInstance()->addGuild($data);
                $guildMemberInfo = [
                    'guild_id' => $guildId,
                    'user_id' => $data['user_id'],
                    'addtime' => date('Y-m-d H:i:s', time()),
                    'status' => 1,
                    'audit_time' => time(),
                ];
                MemberSocityModelDao::getInstance()->addGuildMember($guildMemberInfo);
                return $guildId;
            });

            $data = [
                'guild_id' => $guildId,
                'role' => 1,
            ];
            if ($guildId) {
                UserModelDao::getInstance()->updateDatas($userId, $data);
            }
            return $guildId;
        } catch (Exception $e) {
            Log::error(sprintf('GuildService::createGuild message=%s', $e->getMessage()));
            throw new FQException('创建失败', 500);
        }
    }

    /**
     * 修改公会信息
     * @param $guildId
     * @param $profile
     * @return bool
     * @throws FQException
     */
    public function editGuildInfo($guildId, $profile)
    {
        $editData = [];
        if (array_key_exists('socity', $profile)) {
            $this->editGuildProportion($profile['socity'], $guildId);
            $editData['socity'] = $profile['socity'];
        }
        if (array_key_exists('logo_url', $profile)) {
            $editData['logo_url'] = $profile['logo_url'];
        }
        if (array_key_exists('nickname', $profile)) {
            $editData['nickname'] = $profile['nickname'];
        }
        if (array_key_exists('status', $profile)) {
            $editData['status'] = $profile['status'];
        }
        $updateStatus = MemberGuildModelDao::getInstance()->updateGuildInfo(['id' => $guildId], $editData);
        if ($updateStatus) {
            return true;
        } else {
            throw new FQException('修改失败', 500);
        }
    }

    private function editGuildProportion($editSocity, $guildId)
    {
        if ($editSocity < 0 || $editSocity >= 100) {
            throw new FQException('公会分成比例不能超过百分比、负数', 500);
        }
        $save = $editSocity / 100;
        $guildMembers = MemberSocityModelDao::getInstance()->getGuildMember(['guild_id' => $guildId], 'socity,user_id');
        if (!empty($guildMembers)) {
            foreach ($guildMembers as $guildMember) {
                $memberSocity = $guildMember['socity'] * 100;
                if ($memberSocity > $editSocity) {
                    //如果成员的值大于公会比例的值 把公会成员比例比公会高的调成和公会一样的比例
                    $editGuildMemberStatus = MemberSocityModelDao::getInstance()->editGuildMemberInfo(['guild_id' => $guildId], ['socity' => $save]);
                    $editMemberStatus = UserModelDao::getInstance()->updateDatas($guildMember['user_id'], ['socity' => $save]);
                    if (!$editGuildMemberStatus && !$editMemberStatus) {
                        throw new FQException('修改失败', 500);
                    }
                }
            }
        }
    }

    /**
     * 修改公会成员分成比例
     * @param $userId
     * @param $guildId
     * @param $field
     * @param $value
     * @return bool
     * @throws FQException
     */
    public function editGuildMemberInfo($userId, $guildId, $field, $value)
    {
        if ($value < 0 || $value >= 100) {
            throw new FQException('公会成员分成比例不能超过百分比、负数', 500);
        }
        if ($field == 'socity') {
            $guildInfo = MemberGuildModelDao::getInstance()->loadGuildModel('proportionally', ['guild_id' => $guildId]);
            $proportionally = $guildInfo['proportionally'] * 100;
            if ($value > $proportionally) {
                throw new FQException('公会成员分成比例不能超过公会比例', 500);
            }
            $res = MemberSocityModelDao::getInstance()->editGuildMemberInfo(['user_id' => $userId], [$field => $value / 100]);
            if ($res) {
                $data = ['socity' => $value / 100];
                $result = UserModelDao::getInstance()->updateDatas($userId, $data);
                if (!$result) {
                    throw new FQException('修改失败', 500);
                }
                $method = __FUNCTION__;
                event(new InnerAuditMemberEvent($userId, $data, $method, time()));
            } else {
                throw new FQException('修改失败', 500);
            }
        }
    }

    /**
     * 添加用户加入公会
     * @param $userId
     * @param $guildId
     * @return bool
     * @throws FQException
     */
    public function addGuildMember($userId, $guildId)
    {
        $userModel = UserModelDao::getInstance()->loadUserModel($userId);
        if (empty($userModel)) {
            throw new FQException('用户不存在', 500);
        }
        if (MemberSocityModelDao::getInstance()->getGuidIdByUserId($userId)) {
            throw new FQException('用户已加入其它公会', 500);
        }
        if (MemberGuildModelDao::getInstance()->loadGuildModel('id', ['user_id' => $userId])) {
            throw new FQException('用户已创建了其它公会', 500);
        }
        try {
            $addGuildMemberId = MemberSocityModelDao::getInstance()->addGuildMember([
                'guild_id' => $guildId,
                "user_id" => $userId,
                'socity' => 0.7,
                'status' => 1,
                'addtime' => date('Y-m-d H:i:s', time()),
            ]);
            $data = [
                'guild_id' => $guildId,
                'role' => 1,
            ];
            if ($addGuildMemberId) {
                $editMemberRes = UserModelDao::getInstance()->updateDatas($userId, $data);
                if (!$editMemberRes) {
                    throw new FQException('用户加入公会失败', 500);
                }
            } else {
                throw new FQException('添加用户加入公会失败', 500);
            }

            $method = __FUNCTION__;
            event(new InnerAuditMemberEvent($userId, $data, $method, time()));
            return true;
        } catch (Exception $e) {
            Log::error(sprintf('GuildService::addGuildMember message=%s', $e->getMessage()));
            throw new FQException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * 移除公会成员
     * @param $userId
     * @return bool
     * @throws FQException
     */
    public function removeGuildMember($userId)
    {
        try {
            $data = [
                'role' => 2,
                'guild_id' => 0,
                'socity' => 0.7,
            ];
            $editMemberRes = UserModelDao::getInstance()->updateDatas($userId, $data);
            if ($editMemberRes) {
                $removeUserGuild = MemberSocityModelDao::getInstance()->removeForId($userId);
                if (!$removeUserGuild) {
                    throw new FQException('移除公会成员失败', 500);
                }
            } else {
                throw new FQException('移除失败', 500);
            }

            $method = __FUNCTION__;
            event(new InnerAuditMemberEvent($userId, $data, $method, time()));
            return true;
        } catch (Exception $e) {
            Log::error(sprintf('GuildService::removeGuildMember message=%s', $e->getMessage()));
            throw new FQException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * @param $headUid
     * @param $pkId
     * @param $value
     * @return bool
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function exitMember($headUid, $pkId, $value)
    {
        $memberSocityModel = MemberSocityModelDao::getInstance()->loadModel($pkId);
        if ($memberSocityModel === null) {
            throw new FQException("数据异常不能操作", 500);
        }
        if ((int)$memberSocityModel->status !== 0) {
            throw new FQException("状态已更新，不能操作", 500);
        }

        $guildModel = MemberGuildModelDao::getInstance()->loadGuildModelForId($memberSocityModel->guildId);
        if ($guildModel === null) {
            throw new FQException("公会不存在操作失败", 500);
        }
        if ($guildModel->userId !== $headUid) {
            throw new FQException("公会长才能操作", 500);
        }
        if (!in_array($value, [1, 2])) {
            throw new FQException("操作条件错误,请检查", 500);
        }

        //条件
        list($save, $memberSave, $msg) = $this->loadSaveParam($value, $guildModel->nickname, $memberSocityModel->guildId);

        $guildModel = MemberSocityModelDao::getInstance()->loadForUserGuild($memberSocityModel->userId, $memberSocityModel->guildId);
        if ($guildModel !== null) {
            throw new FQException("该用户不属于当前公会", 500);
        }

        $result = UserModelDao::getInstance()->updateDatas($memberSocityModel->userId, $memberSave);
        if (empty($result)) {
            return false;
        }
        MemberSocityModelDao::getInstance()->updateForId($pkId, $save);
//        notice 小秘书
        YunXinMsg::getInstance()->sendAssistantMsg($memberSocityModel->userId, $msg);
        $method = __FUNCTION__;
        event(new GhAuditMemberEvent($memberSocityModel->userId, $memberSave, $method, time()));
        return true;
    }

    /**
     * @param $value
     * @param $guildName
     * @param $guildId
     * @return array
     */
    private function loadSaveParam($value, $guildName, $guildId)
    {
        $save = [
            "status" => $value,
            "refuse_time" => time(),
        ];
        $memberSave = [
            'guild_id' => 0,
            'socity' => 0,
        ];
        $msg = sprintf("%s公会拒绝了您的加入申请。", $guildName);
        if ($value == 1) {
            $save = [
                "status" => $value,
                "audit_time" => time(),
            ];
            $memberSave = [
                'guild_id' => $guildId,
                'socity' => 0.65,
            ];
            $msg = sprintf("您已加入%s公会。", $guildName);
        }
        return [$save, $memberSave, $msg];
    }

    /**
     * @param $headUid
     * @param $pkId
     * @return \app\core\model\BaseModel|false
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function kickMember($headUid, $pkId)
    {
        $memberSocityModel = MemberSocityModelDao::getInstance()->loadModel($pkId);
        if ($memberSocityModel === null) {
            throw new FQException("数据异常不能操作", 500);
        }
        if ((int)$memberSocityModel->status !== 1) {
            throw new FQException("状态已更新,不能操作", 500);
        }
        $guildModel = MemberGuildModelDao::getInstance()->loadGuildModelForId($memberSocityModel->guildId);
        if ($guildModel === null) {
            throw new FQException("公会不存在操作失败", 500);
        }
        if ($guildModel->userId !== $headUid) {
            throw new FQException("公会长才能操作", 500);
        }
        $data = [
            'role' => 2,
            'guild_id' => 0,
            'socity' => 0.65,
        ];
        $result = UserModelDao::getInstance()->updateDatas($memberSocityModel->userId, $data);
        if (empty($result)) {
            return false;
        }
        MemberSocityModelDao::getInstance()->removeForPk($pkId);
        $msg = sprintf("%s公会和您的合约已解除", $guildModel->nickname);
        if ($result) {
            YunXinMsg::getInstance()->sendAssistantMsg($memberSocityModel->userId, $msg);
        }

        $method = __FUNCTION__;
        event(new GhAuditMemberEvent($memberSocityModel->userId, $data, $method, time()));
        return $result;
    }


    /**
     * @param $headUid
     * @param $pkId
     * @return \app\core\model\BaseModel|false
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function agreeApply($headUid, $pkId)
    {
        $memberSocityModel = MemberSocityModelDao::getInstance()->loadModel($pkId);
        if ($memberSocityModel === null) {
            throw new FQException("数据异常不能操作", 500);
        }
        if ((int)$memberSocityModel->status !== 1) {
            throw new FQException("状态已更新,不能操作", 500);
        }
        $guildModel = MemberGuildModelDao::getInstance()->loadGuildModelForId($memberSocityModel->guildId);
        if ($guildModel === null) {
            throw new FQException("公会不存在操作失败", 500);
        }
        if ($guildModel->userId !== $headUid) {
            throw new FQException("公会长才能操作", 500);
        }
        $data = [
            'role' => 2,
            'guild_id' => 0,
            'socity' => 0.65,
        ];
        $result = UserModelDao::getInstance()->updateDatas($memberSocityModel->userId, $data);
        if (empty($result)) {
            return false;
        }
        MemberSocityModelDao::getInstance()->removeForPk($pkId);
        $msg = sprintf("%s公会通过了您的退会申请", $guildModel->nickname);
        if ($result) {
            YunXinMsg::getInstance()->sendAssistantMsg($memberSocityModel->userId, $msg);
        }

        $method = __FUNCTION__;
        event(new GhAuditMemberEvent($memberSocityModel->userId, $data, $method, time()));
        return $result;
    }


    /**
     * @param $headUid
     * @param $pkId
     * @return \app\core\model\BaseModel
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function refuseApply($headUid, $pkId)
    {
        $memberSocityModel = MemberSocityModelDao::getInstance()->loadModel($pkId);
        if ($memberSocityModel === null) {
            throw new FQException("数据异常不能操作", 500);
        }
        if ((int)$memberSocityModel->status !== 1) {
            throw new FQException("状态已更新,不能操作", 500);
        }
        $guildModel = MemberGuildModelDao::getInstance()->loadGuildModelForId($memberSocityModel->guildId);
        if ($guildModel === null) {
            throw new FQException("公会不存在操作失败", 500);
        }
        if ($guildModel->userId !== $headUid) {
            throw new FQException("公会长才能操作", 500);
        }
//        updatedb
        $data = ['apply_quit_time' => 0];
        $result = MemberSocityModelDao::getInstance()->updateForId($pkId, $data);
//        joinmsg send
        $msg = sprintf("%s公会拒绝了您的退会申请", $guildModel->nickname);
        if ($result) {
            YunXinMsg::getInstance()->sendAssistantMsg($memberSocityModel->userId, $msg);
        }

        return $result;
    }


}












