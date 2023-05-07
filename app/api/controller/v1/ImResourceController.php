<?php


namespace app\api\controller\v1;


use app\api\controller\ApiBaseController;
use app\domain\exceptions\FQException;
use app\domain\forum\dao\ForumBlackModelDao;
use app\domain\imresource\ImBackgroundSystem;
use app\domain\imresource\ImResourceTypes;
use app\domain\imresource\service\ImBackgroundService;
use app\domain\imresource\service\ImBubbleService;
use app\domain\imresource\service\ImEmotionService;
use app\domain\prop\PropSystem;
use app\domain\specialcare\service\UserSpecialCareService;
use app\domain\user\dao\FriendModelDao;
use app\domain\user\service\AttentionService;
use app\domain\vip\service\VipService;
use app\facade\RequestAes as Request;
use app\query\user\QueryUserService;
use app\query\user\service\AttentionService as QueryAttentionService;
use app\query\user\service\VisitorService;
use app\utils\CommonUtil;
use think\facade\Log;

class ImResourceController extends ApiBaseController
{
    /**
     * 获取im气泡、背景、表情包列表
     */
    public function getImResourceList()
    {
        $userId = $this->headUid;
        // im消息气泡
        $allProps = PropSystem::getInstance()->getKindMap();
        $imBubbleProps = [];
        foreach ($allProps as $prop) {
            if ($prop->type == ImResourceTypes::$BUBBLE) {
                $imBubbleProps[] = $this->encodeImBubble($prop);
            }
        }
        // im聊天背景
        $imBackgroundList = ImBackgroundService::getInstance()->getImBackgroundList();
        // im表情包
        $imEmotionList = ImEmotionService::getInstance()->getImEmotionList($userId);
        $result = [
            'im_bubble_list' => $imBubbleProps,
            'im_background_list' => $imBackgroundList,
            'im_emotion_list' => $imEmotionList,
        ];

        return rjson($result);
    }

    /**
     * @desc 消息气泡返回值
     * @param $propKind
     * @return array
     */
    public function encodeImBubble($propKind)
    {
        return [
            'id' => $propKind->kindId,
            'name' => $propKind->name,
            'desc' => $propKind->desc,
            'image' => CommonUtil::buildImageUrl($propKind->image),
            'image_android' => CommonUtil::buildImageUrl($propKind->imageAndroid),
            'bubble_word_image' => CommonUtil::buildImageUrl($propKind->bubbleWordImage),
            'text_color' => $propKind->textColor,
        ];
    }

    /**
     * @desc 设置聊天背景
     * @return \think\response\Json|void
     * @throws FQException
     */
    public function setImBackground()
    {
        $backgroundId = Request::param('background_id');  // 传0值代表取消装扮
        $toUserid = Request::param('to_userid'); // 当前用户和谁聊天 设置的背景图片
        if (!$toUserid) {
            return rjson([], 500, '参数错误');
        }

        $userId = $this->headUid;
        try {
            $isOpenSvip = VipService::getInstance()->isOpenVip($userId, 2);
            if (!$isOpenSvip) {
                throw new FQException('您不是SVIP用户', 500);
            }

            ImBackgroundService::getInstance()->setImBackground($userId, $backgroundId, $toUserid);
        } catch (\Exception $e) {
            Log::error(sprintf('ImController setImBackground Failed userId=%d backgroundId=%d toUserId=%d errmsg=%d',
                $userId, $backgroundId, $toUserid, $e->getTraceAsString()));
            return rjson([], 500, $e->getMessage());
        }

        return rjson();
    }

    /**
     * @desc 设置聊天气泡
     * @return \think\response\Json
     */
    public function setImBubble()
    {
        $bubbleId = Request::param('bubble_id');  // 传0值代表取消装扮
        $userId = $this->headUid;

        try {
            $isOpenSvip = VipService::getInstance()->isOpenVip($userId, 2);
            if (!$isOpenSvip) {
                throw new FQException('您不是SVIP用户', 500);
            }

            ImBubbleService::getInstance()->setImBubble($userId, $bubbleId);
        } catch (\Exception $e) {
            Log::error(sprintf('ImController setImBubble Failed userId=%d id=%d errmsg=%d',
                $userId, $bubbleId, $e->getTraceAsString()));
            return rjson([], 500, $e->getMessage());
        }

        return rjson();
    }

    /**
     * @desc 设置表情包
     * @return \think\response\Json
     */
    public function setImEmotion()
    {
        $emotionGroupId = Request::param('emotion_group_id');
        $action = Request::param('action');  // add  remove
        if (!$emotionGroupId || !$action) {
            return rjson([], 500, '参数错误');
        }

        $userId = $this->headUid;
        try {
            $isOpenSvip = VipService::getInstance()->isOpenVip($userId, 2);
            if (!$isOpenSvip) {
                throw new FQException('您不是SVIP用户', 500);
            }

            ImEmotionService::getInstance()->setImEmotion($userId, $action, $emotionGroupId);
        } catch (\Exception $e) {
            Log::error(sprintf('ImController setImEmotion Failed userId=%d id=%d action=%d errmsg=%d',
                $userId, $emotionGroupId, $action, $e->getTraceAsString()));
            return rjson([], 500, $e->getMessage());
        }

        return rjson();
    }

    /**
     * @desc 获取用户聊天信息相关(im气泡、背景、表情包)
     * @return \think\response\Json
     */
    public function getUserImRelated()
    {
        $toUserid = Request::param('to_userid'); // 当前用户和谁聊天
        $userId = $this->headUid;
        $imBackgroundInfo = $imBubbleInfo = $imBubbleToUserInfo = [];
        // 获取im相关
        $imBackgroundId = ImBackgroundService::getInstance()->getUseImBackground($userId, $toUserid);
        if ($imBackgroundId) {
            $imResourceTypeBackground = ImBackgroundSystem::getInstance()->findKind($imBackgroundId);
            if ($imResourceTypeBackground){
                $imBackgroundInfo = ImBackgroundService::getInstance()->formatImBackground($imResourceTypeBackground);
            }
        }
        // 气泡框
        $imBubbleId = ImBubbleService::getInstance()->getUseImBubble($userId);
        if ($imBubbleId) {
            $propKind = PropSystem::getInstance()->findPropKind($imBubbleId);
            if ($propKind){
                $imBubbleInfo = $this->encodeImBubble($propKind);
            }
        }
        // 获取对方正在使用的气泡框
        $imBubbleToUserId = ImBubbleService::getInstance()->getUseImBubble($toUserid);
        if ($imBubbleToUserId) {
            $propKind = PropSystem::getInstance()->findPropKind($imBubbleToUserId);
            if ($propKind){
                $imBubbleToUserInfo = $this->encodeImBubble($propKind);
            }
        }
        // 获取表情包
        $imEmotion = ImEmotionService::getInstance()->getUseImEmotion($userId);

        // 对方用户信息
        $userModel = QueryUserService::getInstance()->queryUserInfo($userId, $toUserid, 0);
        if (empty($userModel)) {
            return rjson('用户不存在', 500);
        }

        // 关注状态  0未关注、1已关注、2好友
        $attentionStatus = 0;
        $attention = AttentionService::getInstance()->loadAttentionHandleFriend($userId, $toUserid);
        if ($attention) {
            $attentionStatus = 1;
            if (FriendModelDao::getInstance()->loadFriendModel($userId, $toUserid)) {
                $attentionStatus = 2;
            }
        }

        // 是否拉黑
        $blackStatusModel = ForumBlackModelDao::getInstance()->getBlackModel($userId, $toUserid);
        // 备注
        $remarkName = QueryAttentionService::getInstance()->getUserRemark($userId, $toUserid);
        // 特别关注
        $isSpecialCare = UserSpecialCareService::getInstance()->isSpecialCare($userId, $toUserid);
        // 隐身访问
        $isHiddenVisitor = VisitorService::getInstance()->isHiddenVisitor($userId, $toUserid);

        $result = [
            'im_bubble_info' => (object)$imBubbleInfo,
            'im_bubble_to_user_info' => (object)$imBubbleToUserInfo,
            'im_background_info' => (object)$imBackgroundInfo,
            'im_emotion_info' => $imEmotion,
            'attention_status' => $attentionStatus,// 0未关注、1已关注、2好友
            'black_status' => $blackStatusModel ? 1 : 2,// 拉黑状态  1:已拉黑  2:未拉黑
            'remark_name' => $remarkName,
            'special_care_status' => $isSpecialCare ? 1 : 2, // 特别关心状态   1:是 2:不是
            'hidden_visitor_status' => $isHiddenVisitor ? 1 : 2, // 对其隐身访问状态   1:是 2:不是
            'to_user_info' => [
                'user_id' => $userModel->userId,
                'nickname' => $userModel->nickname,
                'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
            ]
        ];

        return rjson($result);
    }
}