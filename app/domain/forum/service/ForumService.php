<?php


namespace app\domain\forum\service;


use app\common\RedisCommon;
use app\common\YunxinCommon;
use app\core\mysql\Sharding;
use app\domain\exceptions\FQException;
use app\domain\forum\dao\ForumBlackModelDao;
use app\domain\forum\dao\ForumEnjoyModelDao;
use app\domain\forum\dao\ForumModelDao;
use app\domain\forum\dao\ForumReplyModelDao;
use app\domain\forum\dao\ForumReportModelDao;
use app\domain\forum\dao\ForumTopicModelDao;
use app\domain\forum\model\ForumEnjoyModel;
use app\domain\forum\model\ForumModel;
use app\domain\forum\model\ForumReplyModel;
use app\domain\user\dao\AccountMapDao;
use app\domain\queue\producer\YunXinMsg;
use app\domain\user\dao\AttentionModelDao;
use app\domain\user\dao\FansModelDao;
use app\domain\user\dao\FriendModelDao;
use app\domain\user\dao\UserModelDao;
use app\event\EnjoyForumEvent;
use app\event\ForumCheckPassEvent;
use app\event\ReleaseDynamicEvent;
use app\event\ReplyForumEvent;
use app\domain\shumei\ShuMeiCheckType;
use app\domain\shumei\ShuMeiCheck;
use app\utils\CommonUtil;
use think\facade\Log;
use Exception;

class ForumService
{
    protected static $instance;
    private $tagKey = 'forum_tags_num';

    //单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new ForumService();
        }
        return self::$instance;
    }


    public function addBlackUser($userId, $blackUserId)
    {
        if (!UserModelDao::getInstance()->isUserIdExists($blackUserId)) {
            throw new FQException('拉黑的用户不存在', 500);
        }

        $model = ForumBlackModelDao::getInstance()->getBlackModel($userId, $blackUserId);
        if ($model != null) {
            throw new FQException('已经拉黑过此用户', 500);
        }

        ForumBlackModelDao::getInstance()->addBlack($userId, $blackUserId);
        YunxinCommon::getInstance()->specializeFriend($userId, $blackUserId, '1', $value = '1');
        YunxinCommon::getInstance()->specializeFriend($blackUserId, $userId, '1', $value = '1');
        Log::info(sprintf('ForumService::addBlackUser ok userId=%d blackUserId=%d',
            $userId, $blackUserId));

        # 所有关系删除
        AttentionModelDao::getInstance()->delAttention($userId, $blackUserId);
        AttentionModelDao::getInstance()->delAttention($blackUserId, $userId);
        FansModelDao::getInstance()->delFans($userId, $blackUserId);
        FansModelDao::getInstance()->delFans($blackUserId, $userId);
        FriendModelDao::getInstance()->delFriend($userId, $blackUserId);
        FriendModelDao::getInstance()->delFriend($blackUserId, $userId);
    }

    public function removeBlackUser($userId, $blackUserId)
    {
        $model = ForumBlackModelDao::getInstance()->getBlackModel($userId, $blackUserId);
        if ($model == null) {
            throw new FQException('此用户不在黑名单', 500);
        }

        ForumBlackModelDao::getInstance()->delBlack($userId, $blackUserId);

        YunxinCommon::getInstance()->specializeFriend($userId, $blackUserId, '1', '0');
        YunxinCommon::getInstance()->specializeFriend($blackUserId, $userId, '1', '0');

        Log::info(sprintf('ForumService::removeBlackUser ok userId=%d blackUserId=%d',
            $userId, $blackUserId));
    }

    //获取
    public function getTags()
    {
        $redis = RedisCommon::getInstance()->getRedis();
        return $redis->ZREVRANGE($this->tagKey, 0, -1);
    }

    //增加标签
    public function incTags($pid, $value)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $redis->ZINCRBY($this->tagKey, $value, $pid);
    }

    //查看回复
    public function readReply($userId, $replyId)
    {
        try {
            Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function() use($replyId) {
                //根据id查询对应的帖子id
                $replyModel = ForumReplyModelDao::getInstance()->loadReplyModel($replyId);
                if ($replyModel == null) {
                    throw new FQException('该评论不存在', 500);
                }

                //更新评论状态
                $replyModel->isRead = 1;
                ForumReplyModelDao::getInstance()->updateReplay($replyModel);

                $forumModel = ForumModelDao::getInstance()->loadForumModel($replyModel->forumId);
                if ($forumModel->status == 4) {
                    throw new FQException('该动态已被删除', 500);
                }
            });
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    //查看点赞
    public function readEnjoy($userId, $likeId)
    {
        try {
            Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function() use($userId, $likeId) {
                //根据id查询对应的帖子id
                $enjoyModel = ForumEnjoyModelDao::getInstance()->loadEnjoyModel($likeId);
                if ($enjoyModel == null) {
                    throw new FQException('该点赞不存在', 200);
                }

                $forumModel = ForumModelDao::getInstance()->loadForumModel($enjoyModel->forumId);
                if ($forumModel->status == 4) {
                    throw new FQException('该动态已被删除', 500);
                }

                //更新点赞状态
                $enjoyModel->isRead = 1;
                ForumEnjoyModelDao::getInstance()->updateEnjoy($enjoyModel);
            });
            return true;
        } catch (Exception $e) {
            throw $e;
        }
    }

    //点赞 type 1点赞 取消点赞
    public function enjoyForum($userId, $forumId, $type)
    {
        $forumModel = ForumModelDao::getInstance()->loadForumModel($forumId);
        if ($forumModel == null || $forumModel->status == 4) {
            throw new FQException('该动态不存在或已被删除', 500);
        }

        try {
            $result = Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function () use($userId, $forumId, $type) {
                $time = time();
                $enjoyModel = ForumEnjoyModelDao::getInstance()->loadEnjoyModelByForumId($userId, $forumId);
                if ($type == 1) {
                    if ($enjoyModel != null) {
                        throw new FQException('已经点赞过', 500);
                    }

                    $model = new ForumEnjoyModel();
                    $model->forumId = $forumId;
                    $model->enjoyUid = $userId;
                    $model->createTime = $time;
                    $model->updateTime = $time;
                    ForumEnjoyModelDao::getInstance()->saveEnjoy($model);
                    $result = 2;
                } else {
                    if ($enjoyModel == null) {
                        throw new FQException('您还没有点赞过', 500);
                    }

                    $enjoyModel->isDel = 1;
                    ForumEnjoyModelDao::getInstance()->updateEnjoy($enjoyModel);
                    $result = 1;
                }
                return $result;
            });
            event(new EnjoyForumEvent($userId,$forumModel->forumUid, $type, time()));
            return $result;
        } catch (Exception $e) {
            throw $e;
        }
    }

    //回复帖子 type 1回帖2评论
    public function replyForum($userId, $forumId, $type, $content, $replyAtuid, $replyParentId)
    {
        if ($type != 1 && $type != 2) {
            throw new FQException('回复类型错误', 500);
        }

        if ($type == 2 && (empty($replyAtuid) || empty($replyParentId))) {
            throw new FQException('回复人不能为空', 500);
        }

//        if (!GreenCommon::getInstance()->checkText($content)) {
//            throw new FQException('当前评论包含色情或敏感字字符', 600);
//        }
        $checkStatus = ShuMeiCheck::getInstance()->textCheck($content,ShuMeiCheckType::$TEXT_COMMENT_EVENT,$userId);
        if(!$checkStatus){
            throw new FQException('评论文字包含敏感字符', 500);
        }

        if (empty(UserModelDao::getInstance()->getBindMobile($userId))) {
            throw new FQException('您还没有绑定手机号', 5100);
        }

        $forumModel = ForumModelDao::getInstance()->loadForumModel($forumId);
        if ($forumModel == null || $forumModel->status == 4) {
            throw new FQException('当前评论动态不存在或已被删除', 500);
        }

        $timestamp = time();
        $replayModel = new ForumReplyModel();
        $replayModel->forumId = $forumId;
        $replayModel->content = $content;
        $replayModel->replyUid = $userId;
        $replayModel->atUid = $replyAtuid;
        $replayModel->parentId = $replyParentId;
        $replayModel->type = $type;
        $replayModel->createTime = $timestamp;
        $replayModel->updateTime = $timestamp;
        $replayModel->status = 1;

        if ($type == 1) {
            $replayModel->atUid = $forumModel->forumUid;
        }

//        触发回复event
        event(new ReplyForumEvent($forumModel->forumUid, $replyAtuid, time()));

        ForumReplyModelDao::getInstance()->saveReplay($replayModel);
    }

    //删除评论
    public function delReply($userId, $replyId)
    {
        $replyModel = ForumReplyModelDao::getInstance()->loadReplyModel($replyId);
        if (empty($replyModel) || $replyModel->status == 3){
            throw new FQException("没有该动态评论或者该评论已被删除", 500);
        }

        $replyModel->delUid = $userId;
        $replyModel->delTime = time();
        $replyModel->status = 3;

        ForumReplyModelDao::getInstance()->updateReplay($replyModel);
    }

    public function shareForum($userId, $forumId)
    {
        try {
            $forumModel = ForumModelDao::getInstance()->loadForumModel($forumId);
            if ($forumModel == null || $forumModel->status == 4) {
                throw new FQException('该动态不存在', 500);
            }

            //更新
            ForumModelDao::getInstance()->incShareNum($forumId, 1);
        } catch (Exception $e) {
            throw $e;
        }
    }

    //删贴
    public function delForum($userId, $forumId)
    {
        try {
            Sharding::getInstance()->getConnectModel('commonMaster', 0)->transaction(function()  use($userId, $forumId) {
                $forumModel = ForumModelDao::getInstance()->loadSelfForumModel($userId, $forumId);
                if ($forumModel == null) {
                    throw new FQException('删除动态不存在', 500);
                }
                if ($forumModel->status == 4) {
                    throw new FQException('动态已经删除', 500);
                }

                $timestamp = time();
                $forumModel->selfDelUid = $userId;
                $forumModel->selfDelTime = $timestamp;
                $forumModel->status = 4;
                $forumModel->updateTime = $timestamp;
                ForumModelDao::getInstance()->updateForum($forumModel);

                $topicModel = ForumTopicModelDao::getInstance()->loadTopicModel($forumId);
                if ($topicModel != null) {
                    $this->incTags($topicModel->pid, -1);
                }
            });
        } catch (Exception $e) {
            throw $e;
        }
    }

    /**
     * @param $userId int 发帖人
     * @param $content
     * @param $image
     * @param $voice
     * @param $forumVoiceTime
     * @param $topicId int 标题id
     * @param $latitudes int 经度
     * @param $longitudes int 纬度
     */
    public function addForum($userId, $topicId, $content, $image, $voice, $forumVoiceTime, $latitudes, $longitudes)
    {
        try {
            $location = CommonUtil::getLocation($longitudes, $latitudes);
            $timestamp = time();
            $forumModel = new ForumModel();
            $forumModel->forumUid = $userId;
            $forumModel->content = $content;
            $forumModel->image = $image;
            $forumModel->voice = $voice;
            $forumModel->createTime = $timestamp;
            $forumModel->updateTime = $timestamp;
            $forumModel->voiceTime = $forumVoiceTime;
            $forumModel->tid = $topicId;
            $forumModel->location = $location;
            $forumModel->status = 3;

            ForumModelDao::getInstance()->insertForum($forumModel);

            $topicModel = ForumTopicModelDao::getInstance()->loadTopicModel($topicId);
            if ($topicModel != null) {
                $this->incTags($topicModel->pid, 1);
            }

//            $this->sendAddForumMsg($userId);
            event(new ReleaseDynamicEvent($userId, $topicId, time()));
        } catch (Exception $e) {
            throw $e;
        }
    }

    public function reportForum($userId, $forumId, $reportOptionId, $reportContent, $replyId)
    {
        $time = time();

        if (!empty($replyId)) {
            $getReport = [
                'forum_id' => $forumId,
                'report_uid' => $userId,
                'reply_id' => $replyId
            ];
            $addParam = [
                'forum_id' => $forumId,
                'report_uid' => $userId,
                'reply_id' => $replyId,
                'report_option_id' => $reportOptionId,
                'report_content' => $reportContent,
                'createtime' => $time,
                'updatetime' => $time
            ];
        } else {
            $getReport = [
                'forum_id' => $forumId,
                'report_uid' => $userId
            ];
            $addParam = [
                'forum_id' => $forumId,
                'report_uid' => $userId,
                'report_option_id' => $reportOptionId,
                'report_content' => $reportContent,
                'createtime' => $time,
                'updatetime' => $time
            ];
        }

        $res = ForumReportModelDao::getInstance()->getOne($getReport, 'id,forum_id');
        if (!empty($res)) {
            throw new FQException('已经举报过', 500);
        }

        $data = ForumReportModelDao::getInstance()->insertData($addParam);
        if (empty($data)) {
            throw new FQException('举报失败', 500);
        }
    }

    private function sendAddForumMsg($userId)
    {
        //云信消息
        $msg = ["msg" => "您的动态已发布成功，平台审核通过后将在动态广场列表中展示。"];
        //queue YunXinMsg
        $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => config('config.fq_assistant'), 'ope' => 0, 'toUid' => $userId, 'type' => 0, 'msg' => $msg]);
        Log::record("forum--msg--data--resMsg" . $resMsg, "info");
    }

    /**
     * @info 修改封禁用户的朋友圈信息
     * @param $userId int uid 封禁的用户userId
     * @param $status int status  状态 0解封 1 封禁
     * @return ForumModelDao|int
     */
    public function forumUpdateForBlockUser($userId, $status)
    {
        $result = 0;
        if ($status === 0) {
            $result = ForumModelDao::getInstance()->rollbackForUserId($userId);
        } elseif ($status == 1) {
            $result = ForumModelDao::getInstance()->delForUserId($userId);
        }
        return $result;
    }

    public function getUserForumImage($where, $page, $pageNum) {
        $images = ForumModelDao::getInstance()->getUserForumImage($where, $page, $pageNum);
        $ret = [];
        foreach ($images as $image) {
            $image = explode(',', $image);
            foreach ($image as $value)
            $ret[] = CommonUtil::buildImageUrl($value);
        }
        return $ret;
    }

    /**
     * 审核动态
     * @param $type
     * @param $forumId
     * @return \think\response\Json
     * @throws FQException
     */
    public function checkForum($type,$forumId)
    {
        $forumModel = ForumModelDao::getInstance()->loadForumModel($forumId);
        if(empty($forumModel)){
            throw new FQException("动态不存在",500);
        }
        if($type == 1){
            if($forumModel->status == 1){
                throw new FQException("动态已被通过",500);
            }
            $forumModel->status = 1;
            $forumModel->examinedTime = time();
            ForumModelDao::getInstance()->updateForum($forumModel);

            event(new ForumCheckPassEvent($forumModel->forumUid, $forumId, $forumModel->tid, time()));
        }else if($type == 2){
            if($forumModel->status == 4){
                throw new FQException("动态已被删除",500);
            }
            $forumModel->status = 4;
            ForumModelDao::getInstance()->updateForum($forumModel);
            //云信消息
            $msg = '您的动态因不符合平台相关规定，未能通过审核，不合规动态已被删除。';
            YunXinMsg::getInstance()->sendAssistantMsg($forumModel->forumUid,$msg);
        }else{
            throw new FQException("参数有误",500);
        }
    }

    /**
     * 后台删除动态
     * @param $forumId
     */
    public function adminDelForum($forumId,$adminUid)
    {
        $forumModel = ForumModelDao::getInstance()->loadForumModel($forumId);
        if(empty($forumModel)){
            throw new FQException("动态不存在",500);
        }
        if($forumModel->status == 4){
            throw new FQException("动态已被删除",500);
        }
        $forumModel->status = 4;
        $forumModel->delUid = $adminUid;
        $forumModel->delTime = time();
        ForumModelDao::getInstance()->updateForum($forumModel);
    }

    /**
     * @desc 设置动态置顶
     * @param $userId
     * @param $forumId
     * @param $type 1:设置  2：取消
     */
    public function setUserForumTop($userId, $forumId, $type)
    {
        if (!in_array($type, [1, 2])) {
            throw new FQException("type取值错误", 500);
        }
        $forumModel = ForumModelDao::getInstance()->loadForumModel($forumId);
        if ($forumModel == null) {
            throw new FQException("当前动态不存在", 500);
        }
        if ($forumModel->status != 1){
            throw new FQException("当前动态未审核通过", 500);
        }
        $time = time();
        if ($type == 1) {
            // 取消之前的动态置顶
            $this->cancelUserForumTop($userId, $time);
            // 新的动态置顶
            $forumModel->isTop = 1;
        }
        if ($type == 2){
            $forumModel->isTop = 0;
        }

        $forumModel->updateTime = $time;
        ForumModelDao::getInstance()->updateForum($forumModel);

        return true;
    }

    /**
     * @desc 获取用户动态置顶帖子
     * @param $userId
     * @return false|mixed|string
     */
    public function getUserForumTop($userId)
    {
        $where = [];
        $where['forum_uid'] = $userId;
        $where['is_top'] = 1;
        $where['forum_status'] = 1;
        return ForumModelDao::getInstance()->loadForumModelWhere($where);
    }

    /**
     * @desc 取消用户动态置顶
     * @param $userId
     * @return bool
     */
    public function cancelUserForumTop($userId, $time)
    {
        $forumTopModel = $this->getUserForumTop($userId);
        if ($forumTopModel) {
            $forumTopModel->isTop = 0;
            $forumTopModel->updateTime = $time;
            ForumModelDao::getInstance()->updateForum($forumTopModel);
        }
        return true;
    }

}