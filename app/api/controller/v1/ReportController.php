<?php
namespace app\api\controller\v1;

use app\common\GetuiCommon;
use app\domain\exceptions\FQException;
use app\query\forum\dao\ForumEnjoyModelDao;
use app\query\forum\dao\ForumModelDao;
use app\query\forum\dao\ForumReportOptionModelDao;
use app\domain\forum\service\ForumService;
use app\query\user\dao\AttentionModelDao;
use app\query\user\cache\UserModelCache;
use app\query\user\service\AttentionService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;
use app\view\ForumView;
use think\facade\Log;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;
use Exception;

class ReportController extends ApiBaseController {

    //举报选项
	public function option()
	{
        $data = ForumReportOptionModelDao::getInstance()->getList();
        if (!empty($data)) {
            return rjson($data);
        } else {
            return rjson();
        }
	}

    //添加举报
	public function report()
	{
		$forumId = Request::param('forum_id');
		$reportContent = Request::param('report_content');
		$reportOptionId = Request::param('report_option_id');
        $replyId = Request::param('repely_id');

        if (!$forumId || !$reportContent || !$reportOptionId) {
        	return rjson('参数错误',500);
        }

		$userId = intval($this->headUid);

        try {
            ForumService::getInstance()->reportForum($userId, $forumId, $reportOptionId, $reportContent, $replyId);
            return rjson([], 200, '举报成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
	}

    /**
     * 点赞
     * @param $forum_id  动态id
     * @param $type     1点赞 2取消赞
     */
    public function enjoy()
    {
        try {
            //获取数据
            $forumId = Request::param('forum_id');
            $type = Request::param('type');
            if (!is_numeric($forumId) || !is_numeric($type)) {
                return rjson([], 500, '参数错误');
            }

            $result = ForumService::getInstance()->enjoyForum($this->headUid, $forumId, $type);

            if($result == 2){
                GetuiCommon::getInstance()->pushMessageToSingle($forumId,0);
            }
            return rjson($result);
        }catch (FQException $e) {
            Log::error(sprintf('ReportController::enjoy $userId=%d ex=%d:%s file=%s:%d',
                $this->headUid, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     *动态点赞列表
     */
    public function forumEnjoyList() {
        $page = Request::param('page');
        if(!$page){
            return rjson([],500, '参数错误');
        }
        try {
            $pageNum = 20;
            $page = empty($page) ? 1 : $page;
            $start = ($page-1) * $pageNum;
            $forumWhere = [['forum_status','in','1'],['forum_uid','=',$this->headUid]];
            $forumModels = ForumModelDao::getInstance()->findForumModelsByWhere($forumWhere, $start, $pageNum);

            $forumList = [];
            foreach ($forumModels as $model) {
                $forumId = $model->forumId;
                $where = [['forum_id', '=', $forumId], ['is_del', '=', 0]];
                $enjoyModels = ForumEnjoyModelDao::getInstance()->findEnjoyModelsByWhere($where, 0, null);
                if(empty($enjoyModels)){
                    continue;
                }

                $data = ForumView::encodeReportFroum($model);
                $data['id'] = $forumId;

                $uidIds = [];
                foreach ($enjoyModels as $enjoyModel) {
                    if(!in_array($enjoyModel->enjoyUid, $uidIds)){
                        $uidIds[] = $enjoyModel->enjoyUid;
                    }
                }
                //用户信息
                $userModelMap = UserModelCache::getInstance()->findUserModelMapByUserIds($uidIds);
                foreach ($uidIds as $userId) {
                    $userModel = ArrayUtil::safeGet($userModelMap, $userId);
                    if (empty($userModel)){
                        continue;
                    }

                    $data['forum_enjoy'][] = [
                        'id' => $userId,
                        'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                        'sex' => $userModel->sex,
                        'nickname' => $userModel->nickname,
                        'friend_status' => $this->getAttentionStatus($this->headUid, $userId)
                    ];
                }

                $forumList[] = $data;
            }

            $enjoyCount = 0;
            $enjoyUnReadCount = 0;
            //获取该用户下所有帖子ID
            $forumModelMap = ForumModelDao::getInstance()->findForumModelMapByWhere([['forum_uid', '=', $this->headUid], ['forum_status', '=', '1']]);
            if ($forumModelMap) {
                $forumIds = array_keys($forumModelMap);
                //所有点赞数量
                $enjoyCount = ForumEnjoyModelDao::getInstance()->getUserForumCount($forumIds);

                \app\domain\forum\dao\ForumEnjoyModelDao::getInstance()->updateReadEnjoyByForumIds($forumIds);

                //点赞未读数量
                $enjoyUnReadCount = \app\domain\forum\dao\ForumEnjoyModelDao::getInstance()->getUserForumUnreadCount($forumIds);
            }

            //返回数据
            $totalPage = ceil($enjoyCount / $pageNum);
            $pageInfo = array('page' => (int)$page, 'pageNum' => $pageNum, 'totalPage' => $totalPage);
            $result = [
                'enjoyCount' => $enjoyUnReadCount,
                'like_list' => $forumList,
                'pageInfo' => $pageInfo,
            ];
            return rjson($result);
        }catch (Exception $e) {
            Log::error(sprintf('ReportController::forumEnjoyList $userId=%d ex=%d:%s file=%s:%d',
                $userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
            return rjson([], 500, '服务器错误');
        }
    }

    /**
     * 动态点赞人列表
     */
    public function forumEnjoyPeopleList() {
        $forumId = Request::param('forum_id');
        if(empty($forumId)) {
            return rjson([],500,'参数错误');
        }
        $page = Request::param('page');
        if(empty($page)){
            return rjson([],500, '参数错误');
        }
        try {
            $pageNum = 20;
            $page = empty($page) ? 1 : $page;
            $start = ($page - 1) * $pageNum;
            $where = [['forum_id', 'in', $forumId], ['is_del', '=', 0]];
            $enjoyModels = ForumEnjoyModelDao::getInstance()->findEnjoyModelsByWhere($where, $start, $pageNum);
            $uidIds = [];
            foreach ($enjoyModels as $model) {
                $uidIds[] = $model->enjoyUid;
            }
            //点赞用户信息
            $userModelMap = UserModelCache::getInstance()->findUserModelMapByUserIds($uidIds);

            $data = [];
            foreach ($enjoyModels as $model) {
                $userId = $model->enjoyUid;
                $userModel = ArrayUtil::safeGet($userModelMap, $userId);
                if (empty($userModel)){
                    continue;
                }

                $data[] = [
                    'id' => $userId,
                    'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                    'sex' => $userModel->sex,
                    'nickname' => $userModel->nickname,
                    'friend_status' => $this->getAttentionStatus($this->headUid, $userId)
                ];
            }

            //所有点赞数量
            $count = 0;
            $forumModelMap = ForumModelDao::getInstance()->findForumModelMapByWhere([['forum_uid', '=', $this->headUid], ['forum_status', 'in', '1']]);
            if (!empty($forumModelMap)){
                $forumIds = array_keys($forumModelMap);
                $count = ForumEnjoyModelDao::getInstance()->getUserForumCount($forumIds);
            }

            $totalPage = ceil($count / $pageNum);
            $pageInfo = array("page" => (int)$page, "pageNum" => $pageNum, "totalPage" => $totalPage);
            //返回数据
            $result = [
                "peopleList" => $data,
                "pageInfo" => $pageInfo,
            ];
            return rjson($result);
        }catch (Exception $e) {
            Log::error(sprintf('ReportController::forumEnjoyPeopleList $userId=%d ex=%d:%s file=%s:%d',
                $userId, $e->getCode(), $e->getMessage(), $e->getFile(), $e->getLine()));
            return rjson([], 500, '服务器错误');
        }
    }

    /**
     * 返回两人关注状态 1未关注 2互相关注  3我关注对方，对方未关注我
     */
    protected function getAttentionStatus($userId, $userIded){
        if (AttentionService::getInstance()->isFocus($userId, $userIded)){
            return AttentionService::getInstance()->isFocus($userIded, $userId) ? 2 : 3;
        }
        return 1;
    }
}
