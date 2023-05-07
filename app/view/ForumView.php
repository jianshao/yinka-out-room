<?php


namespace app\view;


use app\domain\asset\AssetItem;
use app\domain\asset\AssetKindIds;
use app\domain\asset\AssetUtils;
use app\domain\asset\rewardcontent\RandomContent;
use app\domain\forum\model\ForumModel;
use app\domain\forum\model\ForumReplyModel;
use app\domain\forum\model\ForumTopicModel;
use app\domain\task\Task;
use app\domain\task\TaskKind;
use app\utils\CommonUtil;
use app\utils\StringUtil;
use app\utils\TimeUtil;

class ForumView
{

    /**
     * @param $topicModel ForumTopicModel
     * @return array
     */
    public static function encodeFroumTopicTag($topicModel) {
        $data = [
            "id" => $topicModel->id,
            "pid" => $topicModel->pid,
            "tag_name" => $topicModel->topicName,
            "tag_status" => $topicModel->topicStatus,
            "tag_order" => $topicModel->topicOrder,
            "tag_hot" => $topicModel->topicHot,
            "tag_recommend" => $topicModel->topicRecommend,
            "topic_list" => []
        ];
        return $data;
    }

    /**
     * @param $topicModel ForumTopicModel
     * @return array
     */
    public static function encodeFroumTopic($topicModel, $tagName=null) {
        $data = [
            "id" => $topicModel->id,
			"pid" => $topicModel->pid,
			"topic_name" => $topicModel->topicName,
			"topic_status" => $topicModel->topicStatus,
			"topic_order" => $topicModel->topicOrder,
			"topic_hot" => $topicModel->topicHot,
			"topic_recommend" => $topicModel->topicRecommend,
			"create_time" => $topicModel->createTime,
			"update_time" => $topicModel->updateTime,
			"create_user" => $topicModel->createUser,
			"update_user" => $topicModel->updateUser
            ];

        if($tagName != null){
            $data['tag_name'] = $tagName;
        }

        return $data;
    }

    /**
     * @param $froumModel ForumModel
     * @return array
     */
    public static function encodeFroum($froumModel) {
        $data = [
            "forum_id" => $froumModel->forumId,
            "forum_uid" => $froumModel->forumUid,
            "forum_content" => $froumModel->content,
            "forum_voice" => CommonUtil::buildImageUrl($froumModel->voice),
            "createtime" => TimeUtil::timeToStr($froumModel->createTime),
            "examined_time" => $froumModel->examinedTime,
            "forum_voice_time" => $froumModel->voiceTime,
            "tid" => $froumModel->tid,
            "location" => $froumModel->location,
            "share_num" => $froumModel->shareNum,
            "forum_status" => $froumModel->status,
            "is_top" => $froumModel->isTop,
        ];

        if (!empty($froumModel->image)) {
            $imageArr = explode(',', $froumModel->image);
            foreach ($imageArr as $k => &$v) {
                $v = CommonUtil::buildImageUrl($v);
            }
            $data['forum_image'] = implode(',', $imageArr);
            //一张图宽高
            if (count($imageArr) == 1) {
                @$whimg = getimagesize($data['forum_image']);
                @$data['img_w'] = $whimg[0]?$whimg[0]:0;
                @$data['img_h'] = $whimg[1]?$whimg[1]:0;
            }
        }
        return $data;
    }

    /**
     * @param $froumModel ForumModel
     * @return array
     */
    public static function encodeReportFroum($froumModel) {
        $data = [
            "forum_content" => $froumModel->content,
            "forum_voice" => CommonUtil::buildImageUrl($froumModel->voice),
            "forum_image" => CommonUtil::buildImageUrl($froumModel->image),
            "forum_voice_time" => $froumModel->voiceTime,
            "forum_status" => $froumModel->status
        ];

        if (!empty($froumModel->image)) {
            $imageArr = explode(',', $froumModel->image);
            foreach ($imageArr as $k => &$v) {
                $v = CommonUtil::buildImageUrl($v);
            }
            $data['forum_image'] = implode(',', $imageArr);
            //一张图宽高
            if (count($imageArr) == 1) {
                @$whimg = getimagesize($data['forum_image']);
                @$data['img_w'] = $whimg[0]?$whimg[0]:0;
                @$data['img_h'] = $whimg[1]?$whimg[1]:0;
            }
        }
        return $data;
    }

    /**
     * @param $replyModel ForumReplyModel
     * @param $userModelMap
     * @return array
     */
    public static function encodeReplyFroum($forumUid, $replyModel, $replyUserModel, $replyAtUserModel) {
        $data = [
            'reply_id' => $replyModel->replyId,
            'reply_uid' => $replyModel->replyUid,
            'reply_atuid' => $replyModel->atUid,
            'reply_parent_id' => $replyModel->parentId,
            'reply_content' => $replyModel->content,
            'reply_type' => $replyModel->type,
            'createtime' => TimeUtil::timeToStr($replyModel->createTime),
            'reply_uid_avatar' => CommonUtil::buildImageUrl($replyUserModel->avatar),
            'reply_uid_nickname' => $replyModel->replyUid == $forumUid ? '楼主' : $replyUserModel->nickname,
            'is_vip' => $replyUserModel->vipLevel ? $replyUserModel->vipLevel : 0,
            'reply_atuid_atnickname' => $replyModel->atUid == $forumUid ? '楼主' : $replyAtUserModel->nickname,
            'reply_uid_sex' => $replyUserModel->sex,
            'reply_atuid_sex' => $replyAtUserModel->sex
        ];

        return $data;
    }

}
