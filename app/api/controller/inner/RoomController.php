<?php

namespace app\api\controller\inner;

use app\admin\model\LanguageroomModel;
use app\admin\model\MemberGuildModel;
use app\admin\model\PhotoWallModel;
use app\admin\model\RoomModeModel;
use app\Base2Controller;
use app\domain\exceptions\FQException;
use app\domain\guild\cache\HomeHotRoomCache;
use app\domain\guild\cache\RecreationHotRoomCache;
use app\domain\room\model\RoomManagerModel;
use app\domain\room\model\RoomModel;
use app\domain\room\model\RoomTypeModel;
use app\domain\room\service\RoomService;
use app\query\room\service\QueryRoomService;
use app\utils\ArrayUtil;
use app\utils\Error;
use think\facade\Request;

// 房间控制器
class RoomController extends Base2Controller
{

    //首页推荐位
    public function homeHotRoomList()
    {
        $pageNum = Request::param('pageNum', 10, 'intval');
        $roomIds = RoomService::getInstance()->getHomeHotRoomBucket($pageNum);
        $result = [];
        foreach ($roomIds as $roomId => $sort) {
            $itemData['roomId'] = $roomId;
            $model = new HomeHotRoomCache($roomId);
            $itemData['sumHot'] = (int)$model->getHotSum();
            $itemData['sort'] = $sort;
            $result[] = $itemData;
        }
        return rjsonFit(['list' => $result], 200, 'success');
    }

//    娱乐页推荐位置
    public function recreationHotRoomList()
    {
        $pageNum = Request::param('pageNum', 3, 'intval');
        $roomIds = RoomService::getInstance()->getRecommendHotRoomBucket($pageNum);
        $result = [];
        foreach ($roomIds as $roomId => $sort) {
            $itemData['roomId'] = $roomId;
            $model = new RecreationHotRoomCache($roomId);
            $itemData['sumHot'] = (int)$model->getHotSum();
            $itemData['sort'] = $sort;
            $result[] = $itemData;
        }
        return rjsonFit(['list' => $result], 200, 'success');
    }

    // 房间信息审核
    public function roomInfoAudit()
    {
        $operatorId = $this->checkAuthInner();
        $id = Request::param('id', 0, 'intval');
        $status = Request::param('status', 0, 'intval');
        if (empty($id) || empty($status)) {
            return rjson([], 500, '参数错误');
        }

        RoomService::getInstance()->roomInfoAuditHandler($id, $status, $operatorId);
        return rjson([], 200, '操作成功');
    }

    /**
     * @info python api 转过来的http接口
     * @return \think\response\Json
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function queryRoomInfo()
    {
        $roomId = $this->request->param('roomId', 0, 'intval');
        if (empty($roomId)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }

        list($roomModel, $managerList, $roomTypeModel) = QueryRoomService::getInstance()->queryRoomInfo($roomId);
        $result = $this->encodeRoomInfo($roomModel, $managerList, $roomTypeModel);
        return rjson($result, 200, 'success');
    }


    /**
     * @info python api 转过来的http接口
     * @return \think\response\Json
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function searchRoomInfo()
    {
        $searchId = $this->request->param('searchId', 0, 'intval'); # roomId或者房间靓号
        if (empty($searchId)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        list($roomModel, $managerList, $roomTypeModel) = QueryRoomService::getInstance()->searchRoomInfo($searchId);
        $result = $this->encodeRoomInfo($roomModel, $managerList, $roomTypeModel);
        return rjson($result, 200, 'success');
    }

    /**
     * @param RoomModel $roomModel
     * @param RoomManagerModel[] $managerList
     * @param RoomTypeModel $roomTypeModel
     * @return array
     * demo:
     *
     *  /*
     * $result = {
     * 返回的字段        : 对应room里的字段
     * "roomId": "roomId"
     * "roomName": "room_name",
     * "ownerUserId": "user_id",
     * "password":"room_password",
     * "isWheat":"is_wheat",
     * "roomDesc": "room_desc",
     * "roomWelcomes":"room_welcomes",
     * "backgroundImage":"background_image",
     * "guildId":"guild_id"
     * "createTime":"room_createtime"
     * "roomLock":"room_lock"
     * "prettyRoomId":"pretty_room_id"
     * "managerList":[
     * {
     * "userId":1151183,
     * "adminType": 1, # 1管理员 2房主 4超级管理员
     * }
     * ],
     * "roomTypeInfo":{
     * "typeId":"room_type"
     * "parentId":"pid",
     * "modeName":"room_mode",
     * "createTime":"creat_time",
     * "modeType":"mode_type",
     * "status":"status"
     * }
     *
     * }
     */
    private function encodeRoomInfo(RoomModel $roomModel, $managerList, RoomTypeModel $roomTypeModel)
    {
        $managerListData = $this->viewManagerListData($managerList);
        $roomTypeInfo = $this->viewRoomTypeInfo($roomTypeModel);
        $result = [
            "roomId" => $roomModel->roomId,
            "roomName" => $roomModel->name,
            "ownerUserId" => $roomModel->userId,
            "password" => $roomModel->password,
            "isWheat" => $roomModel->isWheat,
            "roomDesc" => $roomModel->desc,
            "roomWelcomes" => $roomModel->welcomes,
            "backgroundImage" => $roomModel->backgroundImage,
            "guildId" => $roomModel->guildId,
            "createTime" => $roomModel->createTime,
            "roomLock" => $roomModel->lock,
            "prettyRoomId" => $roomModel->prettyRoomId,
        ];
        $result['managerList'] = $managerListData;
        $result['roomTypeInfo'] = $roomTypeInfo;
        return $result;
    }

    /**
     * @param RoomTypeModel $roomTypeModel
     * @return array
     */
    private function viewRoomTypeInfo(roomTypeModel $roomTypeModel)
    {
        return [
            'typeId' => $roomTypeModel->id,
            'parentId' => $roomTypeModel->pid,
            'modeName' => $roomTypeModel->roomMode,
            'createTime' => $roomTypeModel->createTime,
            'modeType' => $roomTypeModel->modeType,
            'status' => $roomTypeModel->status,
        ];
    }

    /**
     * @param RoomManagerModel[] $managerModelList
     */
    private function viewManagerListData($managerModelList)
    {
        $result = [];
        foreach ($managerModelList as $managerModel) {
            $itemData = [
                'adminType' => ArrayUtil::safeGet(RoomManagerModel::$viewType, $managerModel->type, 0),
                'userId' => $managerModel->userId,
            ];
            $result[] = $itemData;
        }
        return $result;
    }


    /**
     * @info 后台修改房间信息
     * @return \think\response\Json
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function editRoom()
    {
        $this->checkAuthInner();
        $roomId = Request::param('room_id', 0, 'intval');
        $guildId = Request::param('guild_id', 0, 'intval');
        $tagId = Request::param('tag_id', 0);
        $roomName = Request::param('room_name', '');
        $isHot = Request::param('is_hot');
        $isShow = Request::param('is_show');
        if (empty($roomId) || empty($roomName)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = RoomService::getInstance()->innerEditRoom($roomId, $guildId, $tagId, $roomName, $isHot, $isShow);
        if (empty($result)) {
            return rjson([], 400, '操作失败');
        }
        return rjson([], 200, '操作成功');
    }

    /**
     * @return \think\response\Json
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function roomOssFile()
    {
        $this->checkAuthInner();
        $roomId = Request::param('room_id', 0, 'intval');
        $failureTime = Request::param('failure_time', 0, 'intval');
        $backgroundPath = Request::param('background_image', '');
        if (empty($roomId) || empty($backgroundPath)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = RoomService::getInstance()->innerRoomOssFile($roomId, $failureTime, $backgroundPath);
        if (empty($result)) {
            return rjson([], 400, '操作失败');
        }
        return rjson([], 200, '操作成功');
    }


    public function addRoomPretty()
    {
        $this->checkAuthInner();
        $roomId = Request::param('room_id', 0, 'intval');
        $prettyRoomId = Request::param('pretty_room_id_val', 0, 'intval');
        if (empty($roomId) || empty($prettyRoomId)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = RoomService::getInstance()->innerAddRoomPretty($roomId, $prettyRoomId);
        if (empty($result)) {
            return rjson([], 500, '操作失败');
        }
        return rjson([], 200, '操作成功');
    }

    /**
     * @info 加入公会房间
     * @return \think\response\Json
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addRoomParty()
    {
        $this->checkAuthInner();
        $roomId = Request::param('room_id', 0, 'intval');
        $roomType = Request::param('check_id', 0, 'intval');
        $guildId = Request::param('guild_id', 0, 'intval');

        if ($roomId === 0 || $roomType === 0 || $guildId === 0) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = RoomService::getInstance()->innerAddRoomParty($roomId, $roomType, $guildId);
        if (empty($result)) {
            return rjson([], 500, '操作失败');
        }
        return rjson([], 200, '操作成功');

    }

    /**
     * @info  后台commond调用的房间信息修改接口
     * @return \think\response\Json
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function roomInfoUpdate()
    {
//        $this->checkAuthInner();
        $roomId = Request::param('room_id', 0, 'intval');
        $profileParam = Request::param('profile', "");

        if ($roomId === 0 || empty($profileParam)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $profile = json_decode($profileParam, true);
        if (empty($profile)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = RoomService::getInstance()->innerUpdateRoomForMap($roomId, $profile);
        if (empty($result)) {
            throw new FQException("操作失败", 500);
        }
        return rjson([], 200, '操作成功');
    }

    /**
     * @info
     * @return \think\response\Json
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function addGuidRoomIndex()
    {
        $this->checkAuthInner();
        $guildId = Request::param('guild_id', 0, 'intval');
        $roomId = Request::param('room_id', 0, 'intval');
        if (empty($guildId) || empty($roomId)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = RoomService::getInstance()->innerAddGuildRoomIndex($roomId, $guildId);
        if (empty($result)) {
            throw new FQException("操作失败", 500);
        }
        return rjson([], 200, '操作成功');
    }

    /**
     * @return \think\response\Json
     * @throws FQException
     */
    public function addGuidRoom()
    {
        $this->checkAuthInner();
        $guildId = Request::param('guild_id', 0, 'intval');
        $roomId = Request::param('room_id', 0, 'intval');
        if (empty($guildId) || empty($roomId)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = RoomService::getInstance()->innerAddGuildRoom($roomId, $guildId);
        if (empty($result)) {
            throw new FQException("操作失败", 500);
        }
        return rjson([], 200, '操作成功');
    }

    /**
     * @return \think\response\Json
     */
    public function delGuidRoomIndex()
    {
        $this->checkAuthInner();
        $roomId = Request::param('room_id', 0, 'intval');
        if (empty($roomId)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = RoomService::getInstance()->innerDelGuidRoomIndex($roomId);
        if (empty($result)) {
            throw new FQException("操作失败", 500);
        }
        return rjson([], 200, '操作成功');
    }

    /**
     * @return \think\response\Json
     * @throws FQException
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\DbException
     * @throws \think\db\exception\ModelNotFoundException
     */
    public function delGuidRoom()
    {
        $this->checkAuthInner();
        $roomId = Request::param('room_id', 0, 'intval'); //房间ID
        $guildId = Request::param('guild_id', 0, 'intval'); //公会ID
        $guildType = Request::param('guild_type', 0, 'intval'); // 公会类型， 1公会房间， 0 非公会房
        if (empty($roomId)) {
            throw new FQException(Error::getInstance()->GetMsg(Error::INVALID_PARAMS), Error::INVALID_PARAMS);
        }
        $result = RoomService::getInstance()->innerDelGuidRoom($roomId, $guildId, $guildType);
        if (empty($result)) {
            throw new FQException("操作失败", 500);
        }
        return rjson([], 200, '操作成功');
    }

}
