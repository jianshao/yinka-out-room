<?php

namespace app\api\controller\v1;

use app\common\RedisCommon;
use app\query\notice\dao\NoticeModelDao;
use app\api\controller\ApiBaseController;
use \app\facade\RequestAes as Request;
use app\utils\CommonUtil;
use app\utils\TimeUtil;


class NoticeMsgController extends ApiBaseController
{
	//检测新消息
	public function checkMsg()
	{
        $data = ['exist_nitice' => 0, 'exist_reply' => 0];
        $userId = intval($this->headUid);

        $redis = RedisCommon::getInstance()->getRedis();
        $newNotice = $redis->get('new_notice');
        if ($newNotice) {
            $newNoticeUid = $redis->hget('notice_msg_uid', $userId);
            if (!$newNoticeUid) {
                $data['exist_nitice'] = 1;
            }
        }
        $isexist = $redis->hget('reply_msg_uid', $userId);
        if ($isexist) {
            $data['exist_reply'] = 1;
        }
        return rjson($data);
	}

    public function noticeList()
    {
        $userId = intval($this->headUid);
        if (empty($userId)) {
            return rjson([],500,'id信息错误');
        }

        $res = [];
        NoticeModelDao::getInstance()->updateNoticeTime($userId, time());
        list($notices, $count) = NoticeModelDao::getInstance()->loadNoticeModelsByLimit(0, 10);
        foreach ($notices as $notice) {
            $res[] = [
                'id' => $notice->id,
                'notice_title' => $notice->title,
                'notice_img' => CommonUtil::buildImageUrl($notice->image),
                'notice_content' => $notice->content,
                'notice_status' => $notice->status,
                'timing_time' => $notice->timingTime,
                'created_time' => TimeUtil::timeToStr($notice->createTime),
                'created_user' => $notice->createUser,
                'jump_url' => !empty($notice->jumpUrl)? $notice->jumpUrl."?mtoken=".$this->headToken : $notice->jumpUrl,
            ];
        }

        return rjson($res);
    }

    public function noticeNewList()
    {
        $userId = intval($this->headUid);
        if (empty($userId)) {
            return rjson([],500,'id信息错误');
        }

        $res = [];
        $page = Request::param('page', 1);
        $pageNum = Request::param('page_num', 10);
        $start = ($page - 1) * $pageNum;
        NoticeModelDao::getInstance()->updateNoticeTime($userId, time());
        list($notices, $count) = NoticeModelDao::getInstance()->loadNoticeModelsByLimit($start, $pageNum);
        foreach ($notices as $notice) {
            $res[] = [
                'id' => $notice->id,
                'notice_title' => $notice->title,
                'notice_img' => CommonUtil::buildImageUrl($notice->image),
                'notice_content' => $notice->content,
                'notice_status' => $notice->status,
                'timing_time' => $notice->timingTime,
                'created_time' => TimeUtil::timeToStr($notice->createTime),
                'created_user' => $notice->createUser,
                'jump_url' => !empty($notice->jumpUrl)? $notice->jumpUrl."?mtoken=".$this->headToken : $notice->jumpUrl,
            ];
        }
        if ($this->source == 'chuchu'){
            $res = [];
            $count = 1;
        }
        $data['list'] = $res;
        $data['pageInfo'] = ['page' => $page, 'pageNum' => $pageNum, 'totalPage' => ceil($count / $pageNum)];
        return rjson($data);
    }
}