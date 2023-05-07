<?php

namespace app\api\controller\v1;

use app\api\controller\ApiBaseController;
use app\api\view\v1\RoomView;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\room\dao\RoomHotValueDao;
use app\facade\RequestAes as Request;
use app\query\room\service\QueryRoomService;
use app\query\search\service\SearchService;
use app\query\user\QueryUserService;
use app\utils\ArrayUtil;
use app\utils\CommonUtil;

class SearchController extends ApiBaseController
{
    public function viewRoomList($rooms)
    {
        $ret = [];
        foreach ($rooms as $room) {
            $ret[] = RoomView::searchViewRoom($room);
        }
        return $ret;
    }

    public function viewUserList($users)
    {
        $ret = [];
        foreach ($users as $user) {
            $ret[] = RoomView::searchUserView($user);
        }
        return $ret;
    }

    public function encodeSearchResult($users, $rooms)
    {
        $roomList = $this->viewRoomList($rooms);
        $userList = $this->viewUserList($users);
        return [
            'member_list' => $userList,
            'room_list' => $roomList
        ];
    }

    //搜索房间用户
    public function search()
    {
        $search = Request::param('search');
        $userId = intval($this->headUid);
        $versionCheckStatus = Request::middleware('versionCheckStatus'); //提审状态 1正在提审 0非提审
        if (in_array($search, array('100', '101', '102'))) {
            return rjson([]);
        }
        if ($versionCheckStatus) {
            /* app提审中 */
            list($rooms, $users) = SearchService::getInstance()->searchVersionRoomAndUser($search);
        } else {
            list(list($rooms, $roomTotal), list($users, $userTotal)) = SearchService::getInstance()->searchRoomAndUser($userId, $search, false);
        }
        $rooms = array_slice($rooms, 0, 3);
        $users = array_slice($users, 0, 3);
        $result = $this->encodeSearchResult($users, $rooms);
        //cp匹配
        $userModel = QueryUserService::getInstance()->queryUserInfo($userId, $userId, 0);
        if ($userModel !== null) {
            $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
            $isChannelUser = $redis->zScore('invitcode_list', $userModel->inviteCode);
            if ($userModel->lvDengji == 1 && (time() - strtotime($userModel->registerTime) <= 86400) && $isChannelUser == null) {
                SearchService::getInstance()->cpBindImpl($userId, $users);
            }
        }
        return rjson($result);
    }

    /**
     * @info 搜索房间用户
     * @return \think\response\Json
     */
    public function searchLite()
    {
        $search = Request::param('search');
        $userId = intval($this->headUid);
        $versionCheckStatus = Request::middleware('versionCheckStatus'); //提审状态 1正在提审 0非提审
        if (in_array($search, array('100', '101', '102'))) {
            return rjson([]);
        }
        if ($versionCheckStatus) {
            /* app提审中 */
            list($rooms, $users) = SearchService::getInstance()->searchVersionRoomAndUser($search);
        } else {
            list(list($rooms, $roomTotal), list($users, $userTotal)) = SearchService::getInstance()->searchRoomAndUser($userId, $search, false);
        }
        $rooms = array_slice($rooms, 0, 10);
        $users = array_slice($users, 0, 10);
        $result = $this->encodeSearchResult($users, $rooms);
        //cp匹配
        $userModel = QueryUserService::getInstance()->queryUserInfo($userId, $userId, 0);
        if ($userModel !== null) {
            $redis = RedisCommon::getInstance()->getRedis(['select' => 3]);
            $isChannelUser = $redis->zScore('invitcode_list', $userModel->inviteCode);
            if ($userModel->lvDengji == 1 && (time() - strtotime($userModel->registerTime) <= 86400) && $isChannelUser == null) {
                SearchService::getInstance()->cpBindImpl($userId, $users);
            }
        }
        //        热搜主播
        $anchorUserList = SearchService::getInstance()->loadHotAnchorUserList();
        $result['anchorUser'] = $this->viewUserList($anchorUserList);
        return rjson($result);
    }

    /**
     * @info 搜索过程中
     * @return \think\response\Json
     */
    public function searchProcess()
    {
        $search = Request::param('search');
        $userId = intval($this->headUid);
        $versionCheckStatus = Request::middleware('versionCheckStatus'); //提审状态 1正在提审 0非提审
        if (in_array($search, array('100', '101', '102'))) {
            return rjson([]);
        }
        if ($versionCheckStatus) {
            /* app提审中 */
            list($rooms, $users) = SearchService::getInstance()->searchVersionRoomAndUser($search);
        } else {
            list(list($rooms, $roomTotal), list($users, $userTotal)) = SearchService::getInstance()->searchRoomAndUser($userId, $search, false,false);
        }
        $rooms = array_slice($rooms, 0, 10);
        $users = array_slice($users, 0, 10);
        $result = $this->encodeSearchResult($users, $rooms);
        return rjson($result);
    }


    //搜索更多
    public function searchmore()
    {
        $search = Request::param('search');

        try {
            list(list($rooms, $roomTotal), list($users, $userTotal)) = SearchService::getInstance()->getCache($search);
            $result = $this->encodeSearchResult($users, $rooms);
            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    //搜索历史
    public function searchlog()
    {
        $userId = intval($this->headUid);
        $versionCheckStatus = Request::middleware('versionCheckStatus',0); //提审状态 1正在提审 0非提审
        $logs = SearchService::getInstance()->getSearchLog($userId, 0, 10);
        $data['searchlog'] = $logs;

        $roomDatas = [];
        if($versionCheckStatus){
            $hotRooms = QueryRoomService::getInstance()->queryTsHotRooms(5);
        }else{
            $hotRooms = QueryRoomService::getInstance()->queryHotRooms(0, 20);
        }
        foreach ($hotRooms as $hotRoom) {
            if ($hotRoom->lock === 1) {
                continue;
            }
            $roomDatas[] = [
                'room_id' => $hotRoom->roomId,
                'pretty_room_id' => $hotRoom->prettyRoomId,
                'room_name' => $hotRoom->roomName,
                'room_image' => CommonUtil::buildImageUrl($hotRoom->ownerAvatar),
                'visitor_number' => $hotRoom->visitorNumber,
                'visitor_externnumber' => $hotRoom->visitorExternNumber,
                'visitor_users' => $hotRoom->visitorUsers,
                'user_id' => $hotRoom->ownerUserId,
                'redu' => RoomHotValueDao::getInstance()->getRoomHotValue($hotRoom->roomId)
            ];
        }

        $roomDatas = ArrayUtil::sort($roomDatas, 'redu', SORT_DESC);
        foreach ($roomDatas as $key => $roomData) {
            $roomDatas[$key]['redu'] = formatNumber(floor($roomData['redu']));
        }

        $data['recomroom'] = $roomDatas;
        $followRoomDatas = [];
        list($followRooms, $_) = QueryRoomService::getInstance()->queryFollowRooms($userId, 0, 50);

        foreach ($followRooms as $followRoom) {
            $followRoomDatas[] = [
                'room_id' => $followRoom->roomId,
                'pretty_room_id' => $followRoom->prettyRoomId,
                'room_name' => $followRoom->roomName,
                'room_image' => CommonUtil::buildImageUrl($followRoom->ownerAvatar),
                'visitor_number' => $followRoom->visitorNumber,
                'visitor_externnumber' => $followRoom->visitorExternNumber,
                'visitor_users' => $followRoom->visitorUsers,
                'user_id' => $followRoom->ownerUserId,
                'redu' => RoomHotValueDao::getInstance()->getRoomHotValue($followRoom->roomId)
            ];
        }
        $data['followroom'] = $followRoomDatas;
        return rjson($data);
    }


    public function searchlogLite()
    {
        $userId = intval($this->headUid);
        $versionCheckStatus = Request::middleware('versionCheckStatus', 0); //提审状态 1正在提审 0非提审
        $logs = SearchService::getInstance()->getSearchLog($userId, 0, 10);
        $data['searchlog'] = $logs;
        if($versionCheckStatus){
            $data['anchorUser'] = [];
            $data['guessLike'] = [];
        }else{
            //热搜主播
            $anchorUserList = SearchService::getInstance()->loadHotAnchorUserList();
            $data['anchorUser'] = $this->viewUserList($anchorUserList);
            //猜你喜欢
            $guessLikeList = SearchService::getInstance()->loadGuessLike();
            //循环拼接数据，转view模型
            $data['guessLike'] = RoomView::searchRoomListViewLite($guessLikeList, $this->headUid, $this->source);
        }
        return rjson($data);
    }


    public function searchClear()
    {
        $userId = intval($this->headUid);
        SearchService::getInstance()->clearSearchLog($userId);
        return rjson();
    }
}