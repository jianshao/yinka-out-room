<?php

namespace app\api\controller\v1;

use app\common\RedisCommon;
use app\domain\version\cache\VersionCheckCache;
use app\domain\exceptions\FQException;
use app\domain\vip\service\VipService;
use app\query\forum\dao\ForumBlackModelDao;
use app\query\forum\dao\ForumEnjoyModelDao;
use app\query\forum\dao\ForumModelDao;
use app\query\forum\dao\ForumReplyModelDao;
use app\query\forum\dao\ForumTopicModelDao;
use app\domain\forum\service\ForumService;
use app\query\user\dao\MemberDetailAuditDao;
use app\domain\user\model\MemberDetailAuditActionModel;
use app\query\user\cache\UserModelCache;
use app\query\user\service\AttentionService;
use app\service\CommonCacheService;
use app\service\TencentAuditService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use app\view\ForumView;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;
use app\common\GreenCommon;
use app\domain\shumei\ShuMeiCheckType;
use app\domain\shumei\ShuMeiCheck;
use think\facade\Log;
use Exception;

class ForumController extends ApiBaseController
{
    //动态详情评论列表
    public function replylist()
    {
        try {
            $forumId = Request::param('forum_id');
            $page = Request::param('page');
            $pagenum = Request::param('pagenum');
            if (!$forumId || !$page || !$pagenum) {
                return rjson('参数错误', 500);
            }
            $forumModel = ForumModelDao::getInstance()->loadForumModel($forumId);
            if ($forumModel == null) {
                return rjson("当前动态不存在", 500);
            }

            $start = ($page - 1) * $pagenum;
            $list = [];
            $replyModels = ForumReplyModelDao::getInstance()->findReplyModelsByForumId($forumId, $start, $pagenum);
            $uidIds = [];
            foreach ($replyModels as $model) {
                $uidIds[] = $model->replyUid;
                $uidIds[] = $model->atUid;
            }

            $userModelMap = UserModelCache::getInstance()->findUserModelMapByUserIds($uidIds);
            foreach ($replyModels as $model) {
                $replyUserModel = ArrayUtil::safeGet($userModelMap, $model->replyUid);
                $replyAtUserModel = ArrayUtil::safeGet($userModelMap, $model->atUid);
                if (empty($replyUserModel) || empty($replyAtUserModel)){
                    continue;
                }
                $list[] = ForumView::encodeReplyFroum($forumModel->forumUid, $model, $replyUserModel, $replyAtUserModel);
            }

            $data['page'] = $page;
            $data['pagenum'] = $pagenum;
            $data['pagecount'] = ForumReplyModelDao::getInstance()->getReplyCount($forumId);
            $data['list'] = $list;
            return rjson($data);
        } catch (Exception $e) {
            Log::error(sprintf('replylist Exception userId=%d ex=%d:%s',
                $this->headUid, $e->getCode(), $e->getMessage()));
            return rjson([], 500, '服务器错误');
        }
    }

    //帖子详情
    public function forumdetail()
    {
        $forumId = Request::param('forum_id');
        if (!$forumId) {
            rjson('参数错误', 500);
        }
        try {
            $userid = $this->headUid;

            $forumModel = ForumModelDao::getInstance()->loadForumModel($forumId);
            if ($forumModel == null or $forumModel->status != 1) {
                return rjson([], 500, '当前动态不存在');
            }

            $data = ForumView::encodeFroum($forumModel);
            //点赞评论数
            $data['enjoy_num'] = ForumEnjoyModelDao::getInstance()->getEnjoyCount($forumId);
            $data['reply_num'] = ForumReplyModelDao::getInstance()->getReplyCount($forumId);
            //用户信息
            $userModel = UserModelCache::getInstance()->getUserInfo($forumModel->forumUid);
            $data['avatar'] = $userModel ? CommonUtil::buildImageUrl($userModel->avatar) : '';
            $data['nickname'] = $userModel ? $userModel->nickname : '';
            $data['sex'] = $userModel? $userModel->sex : 1;
            $data['is_vip'] = $userModel->vipLevel;
            $data['age'] = TimeUtil::birthdayToAge($userModel->birthday);
            //点赞关注
            $data['is_attention'] = AttentionService::getInstance()->isFocus($userid, $forumModel->forumUid) ? 1 : 0;
            $data['is_enjoy'] = ForumEnjoyModelDao::getInstance()->loadEnjoyModelByForumId($userid, $forumModel->forumId) ? 1 : 0;
            //话题
            $topicModels = ForumTopicModelDao::getInstance()->loadTopicModel($forumModel->tid);
            $data['topic_name'] = isset($topicModels->topicName) ? $topicModels->topicName : '';
            // 用户备注
            $data['remark_name'] = AttentionService::getInstance()->getUserRemark($userid, $forumModel->forumUid);
            return rjson($data);
        } catch (Exception $e) {
            Log::error(sprintf('forumdetail Exception userId=%d ex=%d:%s',
                $this->headUid, $e->getCode(), $e->getMessage()));
            return rjson([], 500, '服务器错误');
        }

    }

    //帖子评论
    public function addreply()
    {
        $rule = $this->request->rule();
        if ($rule->getRule() == 'v1/forumAddReply') {
            return rjson([], 200, '评论成功');
        }
        try {
            $forumId = Request::param('forum_id');
            $type = Request::param('type');
            $content = Request::param('content');
            $replyAtuid = Request::param('reply_atuid');
            $replyParentId = Request::param('reply_id', 0);
            $content = trim($content);
            if (!$forumId || !$type || $content == '') {
                return rjson([], 500, '参数错误');
            }

            # 1级用户或未实名用户，评论不让发
            $writeList = config('config.write_user_list',[]);
            if (!in_array($this->headUid,$writeList)) {
                $userModel = UserModelCache::getInstance()->getUserInfo($this->headUid);
                if ($userModel->lvDengji == 1 && $userModel->attestation == 0) {
                    return rjson([], 200, '评论成功');
                }
            }

            ForumService::getInstance()->replyForum($this->headUid, $forumId, $type, $content, $replyAtuid, $replyParentId);

            return rjson([], 200, '评论成功');
        } catch (FQException $e) {
            Log::warning(sprintf('addreply Exception userId=%d ex=%d:%s',
                $this->headUid, $e->getCode(), $e->getMessage()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**首页帖子列表
     * @param $page     分页
     * @param $pagenum  条数
     * @param $tid      标签id
     * @return mixed
     */
    public function forumList()
    {
        try {
            $versionCheckStatus = Request::middleware('versionCheckStatus',0); //是否提审中 1正在提审 0非提审
            $page = Request::param('page');
            $pagenum = Request::param('pagenum', 20);
            $tid = Request::param('tid');
            if (!is_numeric($page) || !is_numeric($pagenum)) {
                return rjson([], 500, '参数错误');
            }
            $userId = $this->headUid;
            $data['page'] = $page;
            $data['pagenum'] = $pagenum;
            $data['pagecount'] = ForumModelDao::getInstance()->getForumCountByWhere([['forum_status', 'in', '1']]);
            $data['list'] = $this->getForumData($page, $pagenum, $userId, $this->headUid, 'all', $tid,$versionCheckStatus);
            return rjson($data);
        } catch (Exception $e) {
            Log::error(sprintf('forumList Exception userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            return rjson([], 500, '服务器错误');
        }
    }

    /**
     * @info 处理帖子列表信息
     * @param $page int 页码
     * @param $pagenum int 页数
     * @param $uid int 被访问用户uid
     * @param $user_ids int uid
     * @param $other string 类型
     * @param $tid int 话题id
     * @param $versionCheckStatus int 是否正在提审
     * @return array
     * @throws
     */
    public function getForumData($page, $pagenum, $uid, $user_ids, $other = 'self', $tid = 0, $versionCheckStatus = 0)
    {

        if($versionCheckStatus){
            /* app提审中 */
            $where = $this->getForumVersionDataWhere($tid,$page,$pagenum);
            if(empty($where)) return [];
        }else{
            $where = $this->getForumDataWhere($other,$uid,$tid);
        }
        $start = ($page - 1) * $pagenum;
        if ($other == 'self1' || $other == 'self'){
            $forumModels = ForumModelDao::getInstance()->getForumModelsWithTop($where, $start, $pagenum);
        } else {
            $forumModels = ForumModelDao::getInstance()->findForumModelsByWhere($where, $start, $pagenum);
        }

        if (empty($forumModels)) {
            return [];
        }

        $forumIds = [];
        $uidIds = [];
        foreach ($forumModels as $model) {
            $forumIds[] = $model->forumId;
            $uidIds[] = $model->forumUid;
        }

        //查询点赞评论数量
        $enjoyRes = ForumEnjoyModelDao::getInstance()->getAllNum($forumIds);
        $enjoyResult = array_column($enjoyRes, 'num', 'forum_id');
        $replyRes = ForumReplyModelDao::getInstance()->getAllNum($forumIds);
        $replyResult = array_column($replyRes, 'num', 'forum_id');
        //用户信息
        $userModelMap = UserModelCache::getInstance()->findUserModelMapByUserIds($uidIds);
        //点赞
        $enjoyModelMap = ForumEnjoyModelDao::getInstance()->findEnjoyModelMapByWhere([['enjoy_uid', '=', $user_ids], ['forum_id', 'in', $forumIds], ['is_del', '=', 0]]);
        //话题
        $topicModelMap = ForumTopicModelDao::getInstance()->findAllTopicModelMap();

        $forumList = [];
        foreach ($forumModels as $model) {
            $forumUserId = $model->forumUid;
            $forumId = $model->forumId;
            $data = ForumView::encodeFroum($model);
            $userModel = ArrayUtil::safeGet($userModelMap, $forumUserId);
            if (empty($userModel)){
                continue;
            }
            if ((int)$forumUserId === $uid) {
                $nicknameModel = MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($forumUserId, MemberDetailAuditActionModel::$nickname);
                $userNickname = $nicknameModel->content ?: $userModel->nickname;
                $userAvatar = $userModel->avatar;
                if ($userModel->avatar != 'Public/Uploads/image/male.png' && $userModel->avatar != 'Public/Uploads/image/female.png') {
                    $avatarModel = MemberDetailAuditDao::getInstance()->findMemberDetailAuditForCache($forumUserId, MemberDetailAuditActionModel::$avatar);
                    $userAvatar = $avatarModel->content ?: $userModel->avatar;
                }
            } else {
                $userAvatar = $userModel->avatar;
                $userNickname = $userModel->nickname;
            }

            $data['enjoy_num'] = isset($enjoyResult[$forumId]) ? $enjoyResult[$forumId] : 0;
            $data['reply_num'] = isset($replyResult[$forumId]) ? $replyResult[$forumId] : 0;
            $data['is_attention'] = 0; # 没用 动态列表上没有关注
            $data['is_enjoy'] = isset($enjoyModelMap[$forumId]) ? 1 : 0;
            $data['avatar'] = CommonUtil::buildImageUrl($userAvatar);
            $data['sex'] = $userModel->sex;
            $data['age'] = TimeUtil::birthdayToAge($userModel->birthday);
            $data['nickname'] = $userNickname;
            $data['is_vip'] = $userModel->vipLevel;
            $data['topic_name'] = isset($topicModelMap[$model->tid]->topicName) ? $topicModelMap[$model->tid]->topicName : '';
            $data['tag_name'] = isset($topicModelMap[$model->tid]) ? $topicModelMap[$topicModelMap[$model->tid]->pid]->topicName : "";
            $data['room_id'] = CommonCacheService::getInstance()->getUserCurrentRoom($forumUserId);
            $data['remark_name'] = AttentionService::getInstance()->getUserRemark($user_ids, $forumUserId);

            $forumList[] = $data;
        }
        return $forumList;
    }


    //提审版本 帖子信息
    private function getForumVersionDataWhere($tid,$page,$pageNum){

        $where = [];
        $cacheKey = $tid?sprintf(VersionCheckCache::$topicForumListKey,$tid):VersionCheckCache::$forumListKey;
        $redis = RedisCommon::getInstance()->getRedis();
        $forumIdList = $redis->zRevRange($cacheKey,($page-1)*$pageNum,$page*$pageNum-1);
        if(!empty($forumIdList)){
            $where[] = ['id', 'in',array_values($forumIdList)];
        }
        return $where;

    }

    //帖子信息
    private function getForumDataWhere($other,$uid,$tid){

        $where = array();
        if ($other == 'self1') {   //新版代码
            $where[] = ['forum_uid', '=', $uid];
            $where[] = ['forum_status', 'in', '1,3'];
        } elseif ($other == 'self') {  //旧版
            $where[] = ['forum_uid', '=', $uid];
            $where[] = ['forum_status', 'in', '1'];
        } else {
            //黑名单
            $blackData = ForumBlackModelDao::getInstance()->getToblackIdByBlackId($uid);
            $blackStr = '';
            if (!empty($blackData)) {
                foreach ($blackData as $key => $value) {
                    $blackStr .= $value['toblack_uid'] . ',';
                }
                $blackStr = rtrim($blackStr);
                $where[] = ['forum_uid', 'not in', $blackStr];
            }
            if ($tid != 0) {
                $where[] = ['tid', '=', $tid];
            }
            $where[] = ['forum_status', 'in', '1'];
        }
        return $where;
    }

    /**用户自己帖子列表
     * @param $page     分页
     * @param $pagenum  条数
     * @param $tid      标签id
     * @return mixed
     */
    public function selfforumList()
    {
        try {
            $page = Request::param('page');
            $pagenum = Request::param('pagenum', 20);
            if (!is_numeric($page) || !is_numeric($pagenum)) {
                return rjson([], 500, '参数错误');
            }
            //用户id
            $user_id = Request::param('userid');
            if (empty($user_id)) {
                $user_id = $this->headUid;
            }

            if (!UserModelCache::getInstance()->getUserInfo($user_id)) {
                return rjson([], 500, '此用户不存在');
            }
            //返回数据
            $where = [['forum_status', 'in', '1'], ['forum_uid', '=', $user_id]];
            $data['pagecount'] = ForumModelDao::getInstance()->getForumCountByWhere($where);
            $data['page'] = $page;
            $data['pagenum'] = $pagenum;
            $data['list'] = $this->getForumData($page, $pagenum, $user_id, $this->headUid, 'self');
            return rjson($data);
        } catch (Exception $e) {
            Log::error(sprintf('selfforumList Exception userId=%d ex=%d:%s',
                $this->headUid, $e->getCode(), $e->getMessage()));
            return rjson([], 500, '服务器错误');
        }
    }

    /**
     * 用户自己帖子列表
     * @param $page     分页
     * @param $pagenum  条数
     * @param $tid      标签id
     * @return mixed
     */
    public function newSelfForumList()
    {
        try {
            $page = Request::param('page');
            $pagenum = Request::param('pagenum', 20);
            if (!is_numeric($page) || !is_numeric($pagenum)) {
                return rjson([], 500, '参数错误');
            }
            $other = 'self';
            //用户id
            $userId = Request::param('userid');
            if (empty($userId)) {
                $userId = $this->headUid;
                $other = 'self1';
            } elseif ($userId == $this->headUid) {
                $other = 'self1';
            }

            if (!UserModelCache::getInstance()->getUserInfo($userId)) {
                return rjson([], 500, '此用户不存在');
            }
            //返回数据
            if ($userId != $this->headUid) {
                $where = [['forum_status', 'in', '1'], ['forum_uid', '=', $userId]];
            } else {
                $where = [['forum_status', 'in', '1,3'], ['forum_uid', '=', $userId]];
            }
            $data['pagecount'] = ForumModelDao::getInstance()->getForumCountByWhere($where);

            $data['page'] = $page;
            $data['pagenum'] = $pagenum;
            $data['list'] = $this->getForumData($page, $pagenum, $userId, $this->headUid, $other);
            return rjson($data);
        } catch (Exception $e) {
            Log::error(sprintf('newSelfForumList Exception userId=%d ex=%d:%s',
                $this->headUid, $e->getCode(), $e->getMessage()));
            return rjson([], 500, '服务器错误');
        }

    }


    /**
     * 用户自己帖子图片列表
     * @param $page     分页
     * @param $pagenum  条数
     * @param $tid      标签id
     * @return mixed
     */
    public function selfForumImageList()
    {
        try {
            $page = (int) Request::param('page',1);
            $pageNum = (int) Request::param('pageNum',20);
            $userId = (int) Request::param('userId',0);
            $userModel = UserModelCache::getInstance()->getUserInfo($userId);
            if (empty($userModel)) {
                return rjson([], 500, '用户不存在');
            }
            $where = [['forum_status', '=', '1'], ['forum_uid', '=', $userId], ['forum_image', '<>', '']];
            $data['list'] = ForumService::getInstance()->getUserForumImage($where, $page, $pageNum);
            return rjson($data);
        } catch (Exception $e) {
            Log::error(sprintf('selfForumImageList Exception userId=%d ex=%d:%s',
                $this->headUid, $e->getCode(), $e->getMessage()));
            return rjson([], 500, '未知错误');
        }

    }



    /**发表动态
     * @return mixed
     */
    public function addforum()
    {
        try {
            $content = Request::param('content');
            $voice = Request::param('voice');
            $image = Request::param('image');
            $forum_voice_time = Request::param('forum_voice_time') ? Request::param('forum_voice_time') : 0;
            $topic = Request::param('tid') ? Request::param('tid') : 16;//默认此刻
            $latitudes = Request::param('latitude');         //纬度
            $longitudes = Request::param('longitude');          //经度
            $content = trim($content);
            if ($content == '' && $image == '' && $voice == '') {
                return rjson([], 500, '参数错误');
            }
            if (!is_numeric($latitudes) || !is_numeric($longitudes)) {
                return rjson([], 500, '参数错误');
            }
            if (!empty($image)) {
                //todo 图片检测
                $image = trim($image);
                $imageArr = explode(',', $image);
                if (is_array($imageArr)) {
                    foreach ($imageArr as $key => $value) {
                        if (empty($value)) {
                            return rjson([], 500, '图片参数错误');
                        }
                        $checkStatus = ShuMeiCheck::getInstance()->imageCheck($value,ShuMeiCheckType::$IMAGE_FORUM_EVENT,$this->headUid);
                        if(!$checkStatus){
                            return rjson([], 500, sprintf('第%d张动态图片违反平台规定',$key+1));
                        }
                    }
                } else {
                    return rjson([], 500, '图片参数错误');
                }
            }
            if ($forum_voice_time) {
                if ($forum_voice_time > 60 || $forum_voice_time < 3) {
                    return rjson([], 500, '语音时长不能小于3秒且不能超限60秒');
                }
            }
            //内容检测
            if ($content) {
//                if (!GreenCommon::getInstance()->checkText($content)) {
//                    return rjson([], 500, "当前动态包含色情或敏感字字符");
//                }
                //todo 文本检测
                $checkStatus = ShuMeiCheck::getInstance()->textCheck($content,ShuMeiCheckType::$TEXT_FORUM_EVENT,$this->headUid);
                if(!$checkStatus){
                    return rjson([], 500, "动态文字包含敏感字符");
                }
                $check_content = mb_strlen($content, 'gb2312');
                if ($check_content > 300) {
                    return rjson([], 500, '发表动态不能超过300个字符');
                }
            }
            if(!empty($voice)){
                if (!TencentAuditService::getInstance()->checkVoice($voice)) {
                    throw new FQException('语音介绍违反平台规定');
                }
            }
            ForumService::getInstance()->addForum($this->headUid, $topic, $content, $image, $voice, $forum_voice_time, $latitudes, $longitudes);
            return rjson([], 200, '发布成功');
        } catch (Exception $e) {
            Log::error(sprintf('addforum Exception userId=%d ex=%d:%s',
                $this->headUid, $e->getCode(), $e->getMessage()));
            return rjson([], 500, '发布失败');
        }
    }

    /**删除我的动态
     * @param $forum_id  动态id
     * @return mixed
     */
    public function delforum()
    {
        try {
            $forum_id = Request::param('forum_id');
            if (!is_numeric($forum_id)) {
                return rjson([], 500, '参数错误');
            }
            ForumService::getInstance()->delForum($this->headUid, $forum_id);
            return rjson([], 200, '删除成功');
        } catch (FQException $e) {
            Log::warning(sprintf('delforum Exception userId=%d ex=%s',
                $this->headUid, $e->getTraceAsString()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**标签列表
     * @param $token    token值
     * @return mixed
     */
    public function forumTagList()
    {
        try {
            //查找父标签
            $where['pid'] = 0;
            $where['topic_status'] = 1;
            $topicModels = ForumTopicModelDao::getInstance()->loadTopicModelByWhere($where);

            $tagOrder = [];
            $topic_data = [];
            foreach ($topicModels as $topicModel) {
                $topic_data[$topicModel->id] = ForumView::encodeFroumTopicTag($topicModel);

                //查找父标签对应的子标签
                $topicModelList = ForumTopicModelDao::getInstance()->getTopicModelsByWhere(['pid' => $topicModel->id]);
                foreach ($topicModelList as $model) {
                    $topic_data[$topicModel->id]['topic_list'][] = ForumView::encodeFroumTopic($model);
                }

                $tagOrder[] = $topicModel->id;
            }

            $redisTagOrder = ForumService::getInstance()->getTags();
            foreach ($tagOrder as $topId) {
                if (!in_array($topId, $redisTagOrder)) {
                    $redisTagOrder[] = $topId;
                }
            }

            $tag_list = [];
            foreach ($redisTagOrder as $k => $v) {
                if (isset($topic_data[$v])) {
                    $tag_list[] = $topic_data[$v];
                }
            }

            //热门话题筛选
            $hotwhere[] = ['pid', '<>', 0];
            $hotwhere[] = ['topic_hot', '=', 1];
            $hotTopicModels = ForumTopicModelDao::getInstance()->getTopicModelsByWhere($hotwhere);
            $hot_topic_list = [];
            foreach ($hotTopicModels as $model) {
                $tagName = $topic_data[$model->pid]['tag_name'];
                $hot_topic_list[] = ForumView::encodeFroumTopic($model, $tagName);
            }

            $result = [
                "default_topic_id" => 16,
                "default_topic_name" => '此刻',
                "default_tag_name" => '此刻',
                "tag_list" => $tag_list,
                "hot_topic_list" => $hot_topic_list,
            ];
            return rjson($result);
        } catch (Exception $e) {
            Log::error(sprintf('forumTagList Exception userId=%d ex=%d:%s',
                $this->headUid, $e->getCode(), $e->getMessage()));
            return rjson([], 500, '服务器错误');
        }
    }

    /**更新动态分享次数
     * @return mixed
     */
    public function shareForum()
    {
        try {
            $forumId = Request::param('forum_id');
            ForumService::getInstance()->shareForum($this->headUid, $forumId);
            return rjson([]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /*
     * 动态评论接口
     */
    public function msgReplyList()
    {
        try {
            $page = Request::param('page');
            if (!$page) {
                return rjson([], 500, '参数错误');
            }

            $pageNum = 20;
            $page = empty($page) ? 1 : $page;
            $start = ($page - 1) * $pageNum;

            $replyModels = ForumReplyModelDao::getInstance()->findReplyModelsByAtUid($this->headUid, $start, $pageNum);
            $uidIds = [];
            $fromIds = [];
            foreach ($replyModels as $model) {
                $fromIds[] = $model->forumId;
                $uidIds[] = $model->replyUid;
            }

            //帖子信息
            $forumModelMap = ForumModelDao::getInstance()->findForumModelMapByWhere([['id', 'in', $fromIds]]);
            //用户信息
            $userModelMap = UserModelCache::getInstance()->findUserModelMapByUserIds($uidIds);
            $reply_list = [];
            foreach ($replyModels as $replyModel) {
                $userId = $replyModel->replyUid;
                $userModel = ArrayUtil::safeGet($userModelMap, $userId);
                if (empty($userModel)){
                    continue;
                }

                $data = ForumView::encodeReportFroum($forumModelMap[$replyModel->forumId]);
                $data['msg_reply_id'] = $replyModel->replyId;
                $data['forum_id'] = $replyModel->forumId;
                $data['reply_content'] = $replyModel->content;
                $data['is_read'] = $replyModel->isRead;
                $data['createtime'] = TimeUtil::timeToStr($replyModel->createTime);
                $data['user_id'] = $userId;
                $data['avatar'] = CommonUtil::buildImageUrl($userModel->avatar);
                $data['sex'] = $userModel->sex;
                $data['nickname'] = $userModel->nickname;

                $reply_list[] = $data;
            }

            //获取回帖数量
            $count = ForumReplyModelDao::getInstance()->getAtUidCount($this->headUid);
            $totalPage = ceil($count / $pageNum);
            //评论未读统计
            $reply_count = ForumReplyModelDao::getInstance()->getUnreadCount($this->headUid);
            $pageInfo = array("page" => (int)$page, "pageNum" => $pageNum, "totalPage" => $totalPage);
            //返回数据
            $result = [
                "reply_count" => $reply_count,
                "reply_list" => $reply_list,
                "pageInfo" => $pageInfo,
            ];

            //清除该用户所有评论红点
            \app\domain\forum\dao\ForumReplyModelDao::getInstance()->updateReadReplyByUserIds([$this->headUid]);

            return rjson($result);
        } catch (Exception $e) {
            Log::error(sprintf('msgReplyList Exception userId=%d ex=%d:%s',
                $this->headUid, $e->getCode(), $e->getMessage()));
            return rjson([], 500, '服务器错误');
        }
    }

    /**
     * 评论已未读状态接口
     * type 1 动态 2点赞
     */
    public function repayDetail()
    {
        try {
            $detailId = Request::param('detail_id');
            $type = Request::param('type');
            if (!is_numeric($detailId) || !is_numeric($type)) {
                return rjson([], 500, '参数错误');
            }
            if ($type == 1) {
                ForumService::getInstance()->readReply($this->headUid, $detailId);
            } else {
                ForumService::getInstance()->readEnjoy($this->headUid, $detailId);
            }

            return rjson([], 200, '操作成功');
        } catch (FQException $e) {
            Log::warning(sprintf('repayDetail Exception userId=%d ex=%d:%s',
                $this->headUid, $e->getCode(), $e->getMessage()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * @desc 设置用户动态置顶
     * @return \think\response\Json
     */
    public function setUserForumTop()
    {
        $forumId = Request::param('forum_id');
        $type = intval(Request::param('type'));  // 1:置顶  2:取消置顶
        if (!$type || !$forumId) {
            return rjson([], 500, '参数错误');
        }
        $userId = $this->headUid;
        try {
            $isOpenSvip = VipService::getInstance()->isOpenVip($userId, 2);
            if (!$isOpenSvip) {
                throw new FQException('您不是SVIP用户', 500);
            }
            ForumService::getInstance()->setUserForumTop($userId, $forumId, $type);

        } catch (\Exception $e) {
            Log::error(sprintf('ForumController setUserForumTop Failed userId=%d type=%d errmsg=%d',
                $userId, $type, $e->getTraceAsString()));
            return rjson([], 500, $e->getMessage());
        }

        return rjson();
    }

}