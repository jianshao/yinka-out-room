<?php

namespace app\api\controller\v1;

use app\api\controller\ApiBaseController;
use app\api\view\v1\RoomView;
use app\domain\exceptions\FQException;
use app\domain\room\service\RoomFollowService;
use app\domain\user\service\UserService;
use app\facade\RequestAes as Request;
use app\query\room\service\QueryRoomService;
use app\utils\Error;


class RoomFollowController extends ApiBaseController
{
    /**
     * 隐身用户设置用户信息
     * type 1设置头像，2设置用户名，3设置个签
     */
    public function setUserinfo()
    {
        $type = Request::param('type');
        $data = Request::param('data');
        $userId = intval($this->headUid);
        $avatar = '/Public/Uploads/image/logo.png';
        if ($this->source == 'chuchu'){
            $avatar = '/Public/Uploads/image/qingqinglogo.png';
        }

        $profile = [];
        switch ($type) {
            case 1:
                $profile['avatar'] = $avatar;
                break;
            case 2:
                $profile['nickname'] = $data;
                break;
            case 3:
                $profile['intro'] = $data;
                break;
            default:
                return rjson([], 500, '参数错误');
                break;
        }

        try {
            UserService::getInstance()->editProfile($userId, $profile, $this->channel, $this->version);
            return rjson();
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 返回房间隐身用户id
     * @param string $value [description]
     */
    public function invisUser()
    {
        $redis = $this->getRedis();
        $result = $redis->SMEMBERS('invis_user');
        return rjson($result);
    }

    /**房间关注列表
     * @param $token    token值
     * @return mixed
     */
    public function followList()
    {
        $page = Request::param('page', 0, 'intval');
        if (!$page) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }

        $pageNum = 20;
        $page = empty($page) ? 1 : $page;
        $offset = ($page - 1) * $pageNum;
        list($rooms, $total) = QueryRoomService::getInstance()->queryFollowRooms($this->headUid, $offset, $pageNum);
        $followList = [];
        foreach ($rooms as $room) {
            $followList[] = RoomView::viewQueryRoom($room);
        }

        $result = [
            "follow_list" => $followList,
            "pageInfo" => [
                'page' => $page,
                'pageNum' => $pageNum,
                'totalPage' => ceil($total / $pageNum)
            ],
        ];
        return rjson($result);
    }

    /**关注房间接口
     * @param $token    token值
     * @param $room_id  房间id
     */
    public function attentionRoom()
    {
        //获取数据
        $roomId = intval(Request::param('room_id'));
        if (!$roomId) {
            return rjson([], 500, '参数错误');
        }

        $userId = intval($this->headUid);

        try {
            RoomFollowService::getInstance()->attentionRoom($roomId, $userId);
            return rjson([], 200, '恭喜您关注该房间成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**取消关注房间
     * @param $token    token值
     * @param $room_id  房间id
     */
    public function removeRoom()
    {
        //获取数据
        $roomId = intval(Request::param('room_id'));
        if (!$roomId) {
            return rjson([], 500, '参数错误');
        }

        $userId = intval($this->headUid);

        try {
            RoomFollowService::getInstance()->cancelAttentionRoom($roomId, $userId);
            return rjson([], 200, '取消关注该房间成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}