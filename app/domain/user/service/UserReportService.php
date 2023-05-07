<?php


namespace app\domain\user\service;


use app\domain\dao\UserReportModelDao;
use app\domain\exceptions\FQException;
use app\domain\queue\producer\YunXinMsg;
use app\domain\user\dao\ComplaintUserFollowModelDao;
use app\domain\user\dao\ComplaintUserModelDao;
use app\domain\user\dao\UserModelDao;
use app\domain\user\model\ComplaintUserFollowModel;
use app\domain\user\model\ComplaintUserModel;
use app\domain\user\model\ComplaintUserStatus;
use app\utils\TimeUtil;
use think\facade\Log;

class UserReportService
{
    protected static $instance;

    // 单例
    public static function getInstance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new UserReportService();
        }
        return self::$instance;
    }

    public function reportUser($userId, $toUserId, $contents) {
        //一天只能举报一次该用户
        $data = UserReportModelDao::getInstance()
            ->getModel()
            ->field('create_time')
            ->where([
                'user_id' => $userId,
                'to_uid' => $toUserId
            ])
            ->order('id desc')
            ->find();

        $timestamp = time();
        if (!empty($data) && TimeUtil::isSameDay($timestamp, $data['create_time'])) {
            throw new FQException('您已举报过此用户', 2000);
        }

        UserReportModelDao::getInstance()->getModel()->insert([
            'user_id' => $userId,
            'to_uid' => $toUserId,
            'contents' => $contents,
            'create_time' => $timestamp
        ]);

        Log::info(sprintf('ReportUserService::reportUser ok userId=%d toUserId=%d contents=%s',
            $userId, $toUserId, $contents));
    }


    /**
     * @info 监测用户数据
     * @param $userId
     * @param $toUserId
     * @param $contents
     * @param $description
     * @param $images
     * @param $videos
     * @return int|string
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function complaintUser($userId, $toUserId, $contents, $description, $images, $videos)
    {
        $toUserModel = UserModelDao::getInstance()->loadUserModel($toUserId);
        if ($toUserModel === null) {
            throw new FQException("投诉用户异常", 500);
        }
//        如果已经投诉过该用户，并且投诉未完结报错
        $model = ComplaintUserModelDao::getInstance()->LoadForUid($userId, $toUserId, ComplaintUserStatus::$YIWANJIE);
        if ($model !== null) {
            throw new FQException("您有投诉正在处理中，不可重复提交", 500);
        }
        $unixTime = time();
        $model = new ComplaintUserModel();
        $model->fromUid = $userId;
        $model->toUid = $toUserId;
        $model->contents = $contents;
        $model->description = $description;
        $model->images = $images;
        $model->videos = $videos;
        $model->status = ComplaintUserStatus::$DAICHULI;
        $model->createTime = $unixTime;
        $model->updateTime = 0;
        $result = ComplaintUserModelDao::getInstance()->storeData($model);
        if ($result) {
            $msg = '感谢您的反馈，我们将在24⼩时内处理您的投诉！';
            YunXinMsg::getInstance()->sendAssistantMsg($userId, $msg);
        }
        return $result;
    }


    /**
     * @info 用户投诉的跟进
     * @param $cid
     * @param $content
     * @param $adminId
     * @return int|string
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function complaintUserFollow($cid, $content, $adminId)
    {
//        load 投诉数据，如果已完结则报错
        $model = ComplaintUserModelDao::getInstance()->LoadForCid($cid, ComplaintUserStatus::$YIWANJIE);
        if ($model !== null) {
            throw new FQException("投诉已完结了不能跟进了", 500);
        }
        $unixTime = time();
        $model = new ComplaintUserFollowModel();
        $model->cid = $cid;
        $model->content = $content;
        $model->adminId = $adminId;
        $model->createTime = $unixTime;
        $result = ComplaintUserFollowModelDao::getInstance()->storeData($model);
        if ($result) {
            ComplaintUserModelDao::getInstance()->updateGenjinForAdminId($cid, $adminId, ComplaintUserStatus::$GENJINZHONG);
        }
        return $result;
    }


    /**
     * @param $cid
     * @param $adminId
     * @param $status
     * @return bool
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function complaintUserChange($cid, $adminId, $status)
    {
        if ($status === ComplaintUserStatus::$YIWANJIE) {
//            检索是否有跟进，没有跟进不能完成
            $model = ComplaintUserFollowModelDao::getInstance()->loadFollowData($cid);
            if ($model === null) {
                throw new FQException("没有提交跟进不能关闭投诉哦", 500);
            }
            return ComplaintUserModelDao::getInstance()->updateStatusForAdminId($cid, $adminId,$status);
        }


        return false;
    }
}