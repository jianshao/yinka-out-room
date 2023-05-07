<?php
/*
 * 房间黑名单类
 */

namespace app\api\controller\v1;

use app\api\controller\ApiBaseController;
use app\domain\exceptions\FQException;
use app\domain\room\dao\RoomBlackModelDao;
use app\domain\room\dao\RoomManagerModelDao;
use app\domain\room\service\RoomBlackService;
use app\facade\RequestAes as Request;
use app\query\room\QueryBlackService;
use app\utils\CommonUtil;

class RoomBlackController extends ApiBaseController
{
    /**
     * 双清禁言踢出
     * @param string $value [description]
     */
    public function clearblack()
    {
        $room_id = Request::param('room_id');
        $uid = Request::param('uid');
        $roomFind = RoomManagerModelDao::getInstance()->findManagerByUserId($room_id, $this->headUid);
        if (empty($roomFind)) {
            return rjson([], 500, '无权操作');
        }
        $blackUser = RoomBlackModelDao::getInstance()->loadDataForUserIdRoomId($uid, $room_id);

        if (empty($blackUser)) {
            return rjson([], 500, '已经清除');
        }
        RoomBlackModelDao::getInstance()->removeForRoomUser($room_id, $uid);
        return rjson([], 200, '操作成功');
    }

    /**
     * 禁言 取消禁言
     * @param string $value [description]
     */
    public function estoppel()
    {
        $roomId = intval(Request::param('room_id'));
        $banUserId = intval(Request::param('user_id'));
        $longTime = intval(Request::param('longtime'));
        $type = intval(Request::param('type')); //1禁言 2取消禁言
        $userId = intval($this->headUid);

        try {
            if ($type == 1) {
                RoomBlackService::getInstance()->addBanUser($roomId, $banUserId, $longTime, $userId);
            } else {
                RoomBlackService::getInstance()->removeBanUser($roomId, $banUserId, $userId);
            }
            return rjson([], 200, '操作成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /*
     * 房间黑名单列表
     * @param $token   token值
     * @param $room_id  房间id
     */
    public function roomBlackList()
    {
        //获取数据
        $roomId = Request::param('room_id');
        $page = Request::param('page');
        $type = Request::param('type'); //1踢出 2禁言

        if (!$roomId || !$page || !in_array($type, [1, 2])) {
            return rjson([], 500, '参数错误');
        }

        $pageNum = 20;
        $offset = ($page - 1) * $pageNum;

        try {
            list($blackUsers, $total) = QueryBlackService::getInstance()->listBlackUser($roomId, $type, $offset, $pageNum);
            $blackList = [];
            $timestamp = time();

            foreach ($blackUsers as $blackUser) {
                if ($blackUser->longTime != -1) {
                    $surplusTime = $blackUser->createTime + $blackUser->longTime - $timestamp;
                    $surplusTime = $surplusTime > 0 ? floor($surplusTime) : 0;
                } else {
                    $surplusTime = $blackUser->longTime;
                }

                $blackList[] = [
                    'surplus_time' => $surplusTime > 3 ? $surplusTime : -1,
                    'user_id' => $blackUser->userId,
                    'nickname' => $blackUser->nickname,
                    'avatar' => CommonUtil::buildImageUrl($blackUser->avatar),
                    'createTime' => $blackUser->createTime,
                    'longTime' => $blackUser->longTime,
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

    /**添加黑名单操作
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     */
    public function addBlackUser()
    {
        //获取数据
        $roomId = intval(Request::param('room_id'));
        $blackUserId = intval(Request::param('user_id'));
        $longTime = intval(Request::param('longtime'));

        if (!$roomId || !$blackUserId || !is_numeric($longTime)) {
            return rjson([], 500, '参数错误');
        }

        $userId = intval($this->headUid);

        try {
            RoomBlackService::getInstance()->addBlackUser($roomId, $blackUserId, $longTime, $userId);
            return rjson([], 200, '添加黑名单成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**移除房间黑名单操作
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     */
    public function delBlackUser()
    {
        //获取数据
        $roomId = intval(Request::param('room_id'));
        $blackUserId = intval(Request::param('user_id'));

        if (!$roomId || !$blackUserId) {
            return rjson([], 500, '参数错误');
        }

        $userId = intval($this->headUid);

        try {
            RoomBlackService::getInstance()->removeBlackUser($roomId, $blackUserId, $userId);
            return rjson([], 200, '移除黑名单成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**搜索房间黑名单
     * @param $token    token值
     * @param $room_id      房间id
     * @param $search   搜索值
     */
    public function findBlackUser()
    {
        //获取数据
        $roomId = Request::param('room_id');
        $search = Request::param('search');

        if (!$roomId || !$search) {
            return rjson([], 500, '参数错误');
        }

        if (in_array($search, ['100', '101', '102'])) {
            return rjson([]);
        }

        $userId = intval($this->headUid);

        try {
            list($blackUsers, $total) = QueryBlackService::getInstance()->searchBlackUser($userId, $roomId, $search);
            $blackUserList = [];
            foreach ($blackUsers as $blackUser) {
                $blackUserList[] = [
                    'user_id' => $blackUser->userId,
                    'nickname' => $blackUser->nickname,
                    'sex' => $blackUser->sex,
                    'pretty_id' => $blackUser->userId == $blackUser->prettyId ? 0 : $blackUser->prettyId,
                    'avatar' => CommonUtil::buildImageUrl($blackUser->avatar),
                    'pretty_avatar' => CommonUtil::buildImageUrl($blackUser->prettyAvatar),
                    'type' => $blackUser->type
                ];
            }
            return rjson($blackUserList);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}