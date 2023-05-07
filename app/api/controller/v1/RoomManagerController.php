<?php
/*
 * 房间管理类
 */
namespace app\api\controller\v1;

use app\domain\exceptions\FQException;
use app\domain\room\dao\RoomModelDao;
use app\domain\room\service\RoomManagerService;
use app\query\room\QueryManagerService;
use app\query\user\QueryUserService;
use app\utils\CommonUtil;
use think\facade\Log;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;


class RoomManagerController extends ApiBaseController
{
    public function viewManager($roomId, $queryManager) {
        return [
            'user_id' => $queryManager->userId,
            'nickname' => $queryManager->nickname,
            'avatar' => CommonUtil::buildImageUrl($queryManager->avatar),
            'sex' => $queryManager->sex,
            'pretty_id' => $queryManager->prettyId,
            'pretty_avatar' => CommonUtil::buildImageUrl($queryManager->prettyAvatar),
            'room_id' => $roomId,
            'is_vip' => $queryManager->vipLevel,
            'is_manager' => $queryManager->isManager ? 1: 0
        ];
    }

    /*
     * 房间管理员列表
     * @param $token   token值
     * @param $room_id  房间id
     */
    public function managerList()
    {
        //获取数据
        $roomId = intval(Request::param('room_id'));

        if (!$roomId) {
            return rjson([], 500,'参数错误');
        }

        $userId = intval($this->headUid);

        try {
            list($queryManagers, $total) = QueryManagerService::getInstance()->listManagers($roomId, 0, 30);
            $result = [];
            foreach ($queryManagers as $queryManager) {
                $result[] = $this->viewManager($roomId, $queryManager);
            }
            return rjson($result);
        } catch (FQException $e) {
            Log::error(sprintf('RoomManagerController::addManager roomId=%d userId=%d ex=%s',
                $roomId, $userId, $e->getTraceAsString()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**添加管理员操作
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     */
    public function addManager()
    {
        //获取数据
        $roomId = intval(Request::param('room_id'));
        $managerUserId = intval(Request::param('user_id'));
        if (!$roomId || !$managerUserId) {
            return rjson([], 500, '参数错误');
        }
        $userId = intval($this->headUid);
        try {
            RoomManagerService::getInstance()->addManager($roomId, $managerUserId, $userId);
            return rjson([], 200, '添加管理员成功');
        } catch (FQException $e) {
            Log::error(sprintf('RoomManagerController::addManager roomId=%d userId=%d managerUserId=%d ex=%s',
                $roomId, $userId, $managerUserId, $e->getTraceAsString()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**移除管理员操作
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     */
    public function removeManager()
    {
        //获取数据
        $roomId = intval(Request::param('room_id'));
        $managerUserId = intval(Request::param('user_id'));
        if (!$roomId || !$managerUserId) {
            return rjson([], 500, '参数错误');
        }
        $userId = intval($this->headUid);
        try {
            RoomManagerService::getInstance()->removeManager($roomId, $managerUserId, $userId);
            return rjson([], 200, '移除管理员成功');
        } catch (FQException $e) {
            Log::error(sprintf('RoomManagerController::removeManager roomId=%d userId=%d managerUserId=%d ex=%s',
                $roomId, $userId, $managerUserId, $e->getTraceAsString()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**搜索房间管理员
     * @param $token    token值
     * @param $room_id      房间id
     * @param $search   搜索值
     */
    public function searchManager()
    {
        //获取数据
        $roomId = intval(Request::param('room_id'));
        $search = Request::param('search');

        if (!$roomId || !$search) {
            return rjson([], 500, '参数错误');
        }

        if (in_array($search, array('100','101', '102'))) {
            return rjson([]);
        }

        if ($search == $this->headUid) {
            return rjson([], 500, '不能搜索自己');
        }

        try {
            list($managers, $total) = QueryManagerService::getInstance()->searchManager($roomId, $search);
            $result = [];
            foreach ($managers as $manager) {
                $result[] = $this->viewManager($roomId, $manager);
            }
            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}