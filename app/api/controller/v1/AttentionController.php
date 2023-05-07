<?php
/*
 * 关注管理
 */

namespace app\api\controller\v1;

use app\api\controller\ApiBaseController;
use app\common\GetuiCommon;
use app\common\RedisCommon;
use app\domain\user\dao\FansModelDao;
use app\domain\user\service\AttentionService;
use app\query\forum\dao\ForumEnjoyModelDao;
use app\query\forum\dao\ForumModelDao;
use app\query\forum\dao\ForumReplyModelDao;
use app\domain\exceptions\FQException;
use app\domain\forum\service\ForumService;
use app\domain\queue\producer\YunXinMsg;
use app\query\notice\service\NoticeService;
use app\query\site\service\SiteService;
use app\query\user\dao\FriendModelDao;
use app\query\user\QueryUserService;
use app\query\user\service\AttentionService as QueryAttentionService;
use app\query\forum\QueryForumService;
use app\query\user\cache\UserModelCache;
use app\service\CommonCacheService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\utils\TimeUtil;
use Exception;
use think\facade\Log;
use \app\facade\RequestAes as Request;


class AttentionController extends ApiBaseController
{

    //拉黑用户
    public function addBlackUser()
    {
        $blackUserId = Request::param('uid');
        if (empty($blackUserId)) {
            return rjson([], 500, '参数错误');
        }

        $userId = intval($this->headUid);

        try {
            ForumService::getInstance()->addBlackUser($userId, $blackUserId);
            return rjson([], 200, '拉黑成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    //取消拉黑用户
    public function delBlackUser()
    {
        $blackUserId = Request::param('uid');
        if (empty($blackUserId)) {
            return rjson([], 500, '参数错误');
        }

        $userId = intval($this->headUid);

        try {
            ForumService::getInstance()->removeBlackUser($userId, $blackUserId);
            return rjson([],200,'取消拉黑成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * @param $queryAttention
     * @param $status //status==1不是好友状态 ,status==2 是好友状态
     * @param $curRoomId
     * @return array
     */
    public function viewQueryAttention($queryAttention, $status, $curRoomId, $curUserId)
    {
        return [
            'user_id' => $queryAttention->userId,
            'nickname' => $queryAttention->nickname,
            'intro' => !empty($queryAttention->intro) ? $queryAttention->intro : '如果你主动，我们就有故事',
            'avatar' => CommonUtil::buildImageUrl($queryAttention->avatar),
            'sex' => $queryAttention->sex,
            'lv_dengji' => $queryAttention->lvDengji,
            'is_vip' => $queryAttention->vipLevel,
            'duke_id' => $queryAttention->dukeLevel,
            'attention_time' => TimeUtil::timeToStr($queryAttention->createTime),
            'status' => $status,
            'room_id' => $curRoomId,
            'remark_name' => QueryAttentionService::getInstance()->getUserRemark($curUserId, $queryAttention->userId),
        ];
    }

    /*
     * 关注列表
     * @param $token   token值
     * @param $page    分页
     * @param $type    1关注列表 2粉丝列表 3好友
     */
    public function careUserList()
    {
        //获取数据
        $page = Request::param('page');
        $type = Request::param('type');

        if (!$page || !$type) {
            return rjson([], 500, '参数错误');
        }

        $userId = $this->headUid;
        $pageNum = 20;
        $offset = ($page - 1) * $pageNum;

        if ($type == 2) { // 粉丝列表
            list($attentions, $total) = QueryAttentionService::getInstance()->listFans($userId, $offset, $pageNum);
        } elseif ($type == 3) { // 好友列表
            list($attentions, $total) = QueryAttentionService::getInstance()->listFriend($userId, $offset, $pageNum);
        } else { // 关注
            list($attentions, $total) = QueryAttentionService::getInstance()->listAttention($userId, $offset, $pageNum);
        }

        $attentionList = [];
        if (count($attentions) > 0) {
            foreach ($attentions as $attention) {
                $curRoomId = CommonCacheService::getInstance()->getUserCurrentRoom($attention->userId);

                if ($type == 2) {
                    $status = !empty(FriendModelDao::getInstance()->loadFriendModel($userId, $attention->userId)) ? 2 : 1;
                } elseif ($type == 3) {
                    $status = 2;
                } else {
                    $status = !empty(FriendModelDao::getInstance()->loadFriendModel($userId, $attention->userId)) ? 2 : 1;
                }

                $attentionList[] = $this->viewQueryAttention($attention, $status, $curRoomId, $userId);
            }
        }
        return rjson([
            'list' => $attentionList,
            'pageInfo' => [
                'page' => $page,
                'pageNum' => $pageNum,
                'totalPage' => ceil($total / $pageNum)
            ],
        ]);
    }

    /**批量关注用户
     * @param $userided     用户id '[1,2,3]'
     * @param $type     类型 1 加关 2取关
     */
    public function careUserGroup()
    {
        $type = intval(Request::param('type'));
        $uidArr = Request::param('uids');
        $uidArr = urldecode($uidArr);
        $uidArr = json_decode($uidArr);
        if (!$type || !$uidArr) {
            return rjson([], 500, '参数错误');
        }
        if ($type == 1) {
            $userModel = UserModelCache::getInstance()->getUserInfo($this->headUid);
            if (($userModel->sex == 2 && $userModel->guildId == 0) && $userModel->attestation == 0) {
                return rjson([], 200, '关注成功');
            }
        }
        try {
            AttentionService::getInstance()->attentionUsers($this->headUid, $uidArr, $type);
            if ($type == 1) {
                GetuiCommon::getInstance()->pushMessageToList($uidArr, 0);
            }
            $msg = $type == 1 ? "关注成功" : "取消关注成功";
            return rjson([], 200, $msg);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }


    /**关注用户
     * @param $userided     用户id
     * @param $type     类型 1 加关 2取关
     */
    public function careUser()
    {
        $type = intval(Request::param('type'));
        $userided = Request::param('userided');
        if (!$type || !$userided) {
            return rjson([], 500, '参数错误');
        }
        if ($type == 1) {
            $userModel = UserModelCache::getInstance()->getUserInfo($this->headUid);
            if (($userModel->sex == 2 && $userModel->guildId == 0) && $userModel->attestation == 0) {
                return rjson([], 200, '关注成功');
            }
        }
        AttentionService::getInstance()->attentionUsers($this->headUid, [$userided], $type);

        if ($type == 1) {
            GetuiCommon::getInstance()->pushMessageToSingle($userided, 0);
        }
        $msg = $type == 1 ? '关注成功' : '取消关注成功';
        return rjson([], 200, $msg);
    }

    /**
     * 忽略未读信息
     */
    public function clearAllMsg()
    {
        $type = Request::param('type');
        $user_id = Request::param('user_id');
        if (!$type) {
            return rjson([], 500, '参数错误');
        }
        if ($type == 1) {
            FansModelDao::getInstance()->updateAllUnreadMsgStatus($this->headUid);
        } else {
            FansModelDao::getInstance()->updateUnreadMsgStatus($this->headUid, $user_id);
        }
        $gzCount = FansModelDao::getInstance()->getUnreadMsgCount($this->headUid);
        return rjson($gzCount);
    }

    /**查看某一个粉丝id
     * @param $user_id
     */
    public function clearUserMsg()
    {
        $userId = Request::param('user_id');
        if (!$userId) {
            return rjson([], 500, '参数错误');
        }

        FansModelDao::getInstance()->updateUnreadMsgStatus($this->headUid, $userId);
        return rjson([], 200, '操作成功');
    }

    public function viewCall($queryAttention, $curRoomId)
    {
        return [
            'user_id' => $queryAttention->userId,
            'nickname' => $queryAttention->nickname,
            'intro' => $queryAttention->intro,
            'avatar' => CommonUtil::buildImageUrl($queryAttention->avatar),
            'sex' => $queryAttention->sex,
            'lv_dengji' => $queryAttention->lvDengji,
            'is_vip' => $queryAttention->vipLevel,
            'duke_id' => $queryAttention->dukeLevel,
            'attention_time' => $queryAttention->createTime,
            'saydesc' => '我关注了你,让我们成为玩伴一起玩吧',
            'room_id' => $curRoomId,
            'is_read' => $queryAttention->status == 1 ? true : false
        ];
    }

    /**
     * 打招呼
     */
    public function callList()
    {
        //获取数据
        $page = Request::param('page');
        if (!$page) {
            return rjson([], 500, '参数错误');
        }
        $userId = $this->headUid;
        $page = empty($page) ? 1 : $page;
        $pageNum = 20;
        $offset = ($page - 1) * $pageNum;

        try {
            list($attentions, $total) = QueryAttentionService::getInstance()->listFans($userId, $offset, $pageNum);

            $attentionList = [];
            if (count($attentions) > 0) {
                foreach ($attentions as $attention) {
                    $curRoomId = CommonCacheService::getInstance()->getUserCurrentRoom($attention->userId);
                    $attentionList[] = $this->viewCall($attention, $curRoomId);
                }
            }

            $unreadCount = QueryAttentionService::getInstance()->getUnreadMsgCount($userId);
            $result = [
                'readCount' => $unreadCount,
                'list' => $attentionList,
                'pageInfo' => [
                    'page' => $page,
                    'pageNum' => $pageNum,
                    'totalPage' => ceil($total / $pageNum)
                ]
            ];

            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 消息统计
     */
    public function userMsgCount()
    {
        //统计打招呼
        $gzCount = QueryAttentionService::getInstance()->getUnreadMsgCount($this->headUid);
        //评论统计
        $replayCount = ForumReplyModelDao::getInstance()->getUnreadCount($this->headUid);
        //点赞统计
        $enjoyCount = 0;
        //获取该用户下所有帖子ID
        $forumModelMap = ForumModelDao::getInstance()->findForumModelMapByWhere([['forum_uid', '=', $this->headUid], ['forum_status', '=', '1']]);
        if ($forumModelMap) {
            $forumIds = array_keys($forumModelMap);
            $enjoyCount = ForumEnjoyModelDao::getInstance()->getUserForumUnreadCount($forumIds);
        }
        $result = [
            'gzCount' => $gzCount,
            'replayCount' => $replayCount,
            'enjoyCount' => $enjoyCount
        ];
        return rjson($result);
    }

    /**好友搜索 搜索昵称及用户Id
     * @param $search
     */
    public function searchFriend()
    {
        $search = Request::param('search');
        $userId = intval($this->headUid);

        try {
            list($queryUsers, $total) = QueryUserService::getInstance()->searchUsers($search, 0, 50, [$userId]);

            $friendList = [];
            if (!empty($queryUsers)) {
                $userIds = [];
                foreach ($queryUsers as $queryUser) {
                    $userIds[] = $queryUser->userId;
                }

                $friendMap = FriendModelDao::getInstance()->findMapByFriendIds($userId, $userIds);

                foreach ($queryUsers as $queryUser) {
                    $curRoomId = CommonCacheService::getInstance()->getUserCurrentRoom($queryUser->userId);
                    $friendList[] = [
                        'user_id' => $queryUser->userId,
                        'pretty_id' => $queryUser->prettyId,
                        'nickname' => $queryUser->nickname,
                        'avatar' => CommonUtil::buildImageUrl($queryUser->avatar),
                        'sex' => $queryUser->sex,
                        'lv_dengji' => $queryUser->lvDengji,
                        'is_vip' => $queryUser->vipLevel,
                        'intro' => $queryUser->intro,
                        'is_friend' => ArrayUtil::safeGet($friendMap, $queryUser->userId),
                        'room_id' => $curRoomId,
                        'duke_id' => $queryUser->dukeLevel
                    ];
                }
            }
            return rjson($friendList);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 消息列表
     */
    public function msgList()
    {
        //官方消息
        try {
            $userModel = UserModelCache::getInstance()->getUserInfo($this->headUid);
            list($noticeModels, $count) = NoticeService::getInstance()->loadNotice(1);
            if (count($noticeModels) == 0) {
                $notice_msg_num = 0;
                $created_time = time();
                $notice_title = '';
            } else {
                $notice = $noticeModels[0];
                $endtime = NoticeService::getInstance()->getLastNoticeTime($this->headUid);
                $notice_msg_num = NoticeService::getInstance()->hasNewNotice($endtime, $notice->createTime, $userModel->registerTime);
                $created_time = $notice->createTime;
                $notice_title = $notice->title;
            }

            //获取动态消息未读   评论消息+点赞消息
            $forum_msg_count = ForumReplyModelDao::getInstance()->getUnreadCount($this->headUid);
            //查找最后一条评论
            $replyModels = ForumReplyModelDao::getInstance()->findReplyModelsByAtUid($this->headUid, 1, null);
            if (count($replyModels) != 0) {
                $replyModel = $replyModels[0];
                $reply_nickname = UserModelCache::getInstance()->findNicknameByUserId($replyModel->replyUid) . "评论: ";
                $reply_time = date('Y-m-d H:i:s', $replyModel->createTime);
                $reply_content = $reply_nickname . $replyModel->content;

                $where[] = ['createtime', '>', $replyModel->createTime];
            } else {
                $reply_content = '';
                $reply_time = "";
            }

            //点赞统计
            $enjoyCount = 0;
            $forumModels = ForumModelDao::getInstance()->findForumModelsByWhere([['forum_uid', '=', $this->headUid], ['forum_status', '=', '1']], 0, null);
            if ($forumModels) {
                $forumIds = [];
                foreach ($forumModels as $forumModel){
                    $forumIds[] = $forumModel->forumId;
                }
                $enjoyCount = ForumEnjoyModelDao::getInstance()->getUserForumUnreadCount($forumIds);

                $where[] = ['forum_id', '=', $forumModels[0]->forumId];
                $where[] = ['is_del', '=', 0];
                //查找最后一条点赞
                $enjoyModel = ForumEnjoyModelDao::getInstance()->findLastEnjoyModelByWhere($where);
                if ($enjoyModel) {
                    $reply_content = "有人赞了你的动态！";
                    if ($enjoyModel->createTime == null) {
                        $reply_time = date('Y-m-d H:i:s', strtotime("-1 days"));
                    } else {
                        $reply_time = date('Y-m-d H:i:s', $enjoyModel->createTime);
                    }
                }
            }
            $forum_msg_count = $forum_msg_count + $enjoyCount;


            //获取打招呼消息读
            $att_count = QueryAttentionService::getInstance()->getUnreadMsgCount($this->headUid);
            $attModel = QueryAttentionService::getInstance()->loadNewFansModel($this->headUid);
            if ($attModel == null) {
                $att_nickname = '';
                $attention_time = "";
            } else {
                $attention_time = TimeUtil::timeToStr($attModel->createTime);
                $att_nickname = UserModelCache::getInstance()->findNicknameByUserId($attModel->fansId) . "向你打招呼";
            }

            $image1 = CommonUtil::buildImageUrl('/image/msgcoin/104.png');
            $image2 = CommonUtil::buildImageUrl('/image/msgcoin/102.png');
            $image3 = CommonUtil::buildImageUrl('/image/msgcoin/106.png');
            if ($this->source == 'chuchu'){
                $image1 = CommonUtil::buildImageUrl('/image/msgcoin/107.png');
                $image3 = CommonUtil::buildImageUrl('/image/msgcoin/109.png');
            }
            $title = "官方消息";
            $list = array(
                '1' => array("id" => 101, 'image' => $image1, 'title' => $title, 'depict' => $notice_title, 'msgCount' => $notice_msg_num, 'endtime' => date('Y-m-d H:i:s', $created_time)),
                '2' => array("id" => 102, 'image' => $image2, 'title' => "动态消息", 'depict' => $reply_content, 'msgCount' => $forum_msg_count, 'endtime' => $reply_time),
                '3' => array("id" => 103, 'image' => $image3, 'title' => $att_count > 0 ? "有" . $att_count . "个人和你打招呼" : "打招呼消息", 'depict' => $att_nickname, 'msgCount' => $att_count, 'endtime' => $attention_time),
            );

            $lists = array_values($list);
            return rjson($lists);
        } catch (Exception $e) {
            Log::error(sprintf('AttentionController::msgList userId=%d ex=%s',
                intval($this->headUid), $e->getTraceAsString()));
            return rjson([], 500, '服务器错误');
        }
    }

    /**
     * 拉黑用户列表
     */
    public function blackList()
    {
        //获取数据
        $page = Request::param('page');
        if (!$page) {
            return rjson([], 500, '参数错误');
        }
        $userId = intval($this->headUid);
        $pageNum = 20;
        $page = empty($page) ? 1 : $page;
        $offset = ($page - 1) * $pageNum;

        try {
            list($blackUsers, $total) = QueryForumService::getInstance()->listBlackUser($userId, $offset, $pageNum);
            $blackList = [];
            foreach ($blackUsers as $blackUser) {
                $curRoomId = CommonCacheService::getInstance()->getUserCurrentRoom($blackUser->userId);
                $blackList[] = [
                    'user_id' => $blackUser->userId,
                    'nickname' => $blackUser->nickname,
                    'intro' => $blackUser->intro,
                    'avatar' => CommonUtil::buildImageUrl($blackUser->avatar),
                    'sex' => $blackUser->sex,
                    'lv_dengji' => $blackUser->lvDengji,
                    'is_vip' => $blackUser->vipLevel,
                    'createtime' => $blackUser->createTime,
                    'room_id' => $curRoomId
                ];
            }
            return rjson([
                'list' => $blackList,
                'pageInfo' => [
                    'page' => $page,
                    'pageNum' => $pageNum,
                    'totalPage' => ceil($total / $pageNum)
                ]
            ]);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 拍一拍
     */
    public function TakeShot()
    {
        try {
            $fromUserId = $this->headUid;
            $toUid = Request::param('toUid');

            if (!is_numeric($toUid) || !$toUid) {
                return rjson('', 500, '参数错误');
            }

            $fromNickname = UserModelCache::getInstance()->findNicknameByUserId($fromUserId);
            $toUserName = UserModelCache::getInstance()->findNicknameByUserId($toUid);

            //获取拍一拍的形容词
            $redis = RedisCommon::getInstance()->getRedis();

            //设置戳一戳的消息的有效时间
            //获取戳一戳的消息过期时间
            $is_take = $redis->get($fromUserId . '_takeshot_' . $toUid);
            if ($is_take) {
                return rjson('', 500, '今天戳过他了呦~');
            }

            $filterKey = sprintf("takeshot_user_filter:%s", $this->headUid);
            $filterNumber = $redis->incr($filterKey);
            if ($filterNumber > 3) {
                return rjson('', 500, '次数已经用完了，明天再来吧');
            }
            if ($filterNumber === 1) {
                $todayStr = date("Y-m-d");
                $expireTime = strtotime($todayStr) + 86400 - time();
                $redis->expire($filterKey, $expireTime);
            }
            //女性且非工会或女性非未实名的用户，不能使用【戳一下】的功能
            $userModel = UserModelCache::getInstance()->getUserInfo($fromUserId);
            if (($userModel->sex == 2 && $userModel->guildId == 0) && $userModel->attestation == 0) {
                return rjson([], 200, '发送成功');
            }
            $pokeWords = $redis->get('pokewords_cache');
            $pokeWordsArr = json_decode($pokeWords, true);
            if (empty($pokeWords)) {
                $siteConf = SiteService::getInstance()->getSiteConf(1);
                $pokeWordsArr = json_decode($siteConf['poke_words'], true);
                $redis->set('pokewords_cache', $siteConf['poke_words']);
            }
            $rand = rand(0, count($pokeWordsArr) - 1);
            $describe = $pokeWordsArr[$rand];
            $msg = ['fromUid' => $fromUserId, 'fromName' => $fromNickname, 'toUid' => $toUid, 'toName' => $toUserName, 'describe' => $describe, 'type' => 'takeShot'];
            //queue YunXinMsg
            $resMsg = YunXinMsg::getInstance()->sendMsg(['from' => $fromUserId, 'ope' => 0, 'toUid' => $toUid, 'type' => 100, 'msg' => $msg]);
            Log::info(sprintf('TakeShot fromUId=%d toUid=%d, resMsg=%s', $fromUserId, $toUid, $resMsg));
            //获取过期时间
            $startTime = time();
            $endTime = strtotime(date('Y-m-d', strtotime('+1day')));
            $expTime = $endTime - $startTime;
            $redis->setex($fromUserId . '_takeshot_' . $toUid, $expTime, 1);
            return rjson([], 200, '发送成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * @desc 设置备注
     * @return \think\response\Json
     */
    public function setUserRemark()
    {
        $toUserid = Request::param('to_userid');
        $remarkName = Request::param('remark_name');
        if (!$toUserid) {
            return rjson([], 500, '参数错误');
        }

        $userId = $this->headUid;

        try {
            $isFocus = QueryAttentionService::getInstance()->isFocus($userId, $toUserid);
            if (!$isFocus) {
                throw new FQException('请先关注该用户', 500);
            }

            AttentionService::getInstance()->setUserRemark($userId, $toUserid, $remarkName);
        } catch (\Exception $e) {
            Log::error(sprintf('AttentionController setUserRemark Failed userId=%d touserId=%d remarkName=%d errmsg=%d',
                $userId, $toUserid, $remarkName, $e->getTraceAsString()));
            return rjson([], 500, $e->getMessage());
        }

        return rjson();
    }

}