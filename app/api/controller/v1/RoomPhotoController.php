<?php
/*
 * 房间管理类
 */
namespace app\api\controller\v1;

use app\domain\exceptions\FQException;
use app\domain\gift\GiftSystem;
use app\domain\room\dao\RoomPhotoModelDao;
use app\domain\room\service\RoomPhotoService;
use app\query\user\cache\UserModelCache;
use app\utils\CommonUtil;
use think\facade\Log;
use \app\facade\RequestAes as Request;
use app\api\controller\ApiBaseController;


class RoomPhotoController extends ApiBaseController
{
    /*
     * 房间相册列表
     * @param $token   token值
     * @param $roomId  房间id
     * @param $userType  用户类型 1是普通用户 2是管理员
     */
    public function roomPhotoList()
    {
        //获取数据
        $roomId = intval(Request::param('roomId'));
        $userType = intval(Request::param('userType'));
        $photoId = intval(Request::param('photoId', 0));
        $length = intval(Request::param('length', 6));
        if (!$roomId || !$userType || !$length) {
            return rjson([], 500,'参数错误');
        }

        $userId = intval($this->headUid);

        try {
            if ($userType == 1){
                $roomPhotos = [];
//                $roomPhotos = RoomPhotoModelDao::getInstance()->loadNormalPhotos($roomId, $photoId, $length);
            }else{
                $roomPhotos = RoomPhotoModelDao::getInstance()->loadAllPhotos($roomId, $photoId, $length);
            }
            $result = [];
            foreach ($roomPhotos as $roomPhoto) {
                $giftKind = GiftSystem::getInstance()->findGiftKind($roomPhoto->giftId);
                if ($giftKind == null) {
                    continue;
                }

                $userModel = UserModelCache::getInstance()->getUserInfo($roomPhoto->userId);
                if ($userModel == null) {
                    continue;
                }

                $result[] = [
                    'userId' => $userModel->userId,
                    'userAvatar' => CommonUtil::buildImageUrl($userModel->avatar),
                    'userNickname' => $userModel->nickname,
                    'photoId' => $roomPhoto->photoId,
                    'giftId' => $roomPhoto->giftId,
                    'giftImage' => CommonUtil::buildImageUrl($giftKind->image),
                    'giftPrice' => $giftKind->price ? $giftKind->price->count : 0,
                    'isUnLock' => RoomPhotoModelDao::getInstance()->isUnLockPhoto($roomId, $userId, $roomPhoto->photoId),
                    'photoImage' => CommonUtil::buildImageUrl($roomPhoto->image),
                    'status' => $roomPhoto->status
                ];
            }
            return rjson([
                'roomId' => $roomId,
                'photoList' => $result
            ]);
        } catch (FQException $e) {
            Log::error(sprintf('RoomPhotoController::roomPhotoList roomId=%d userId=%d ex=%s',
                $roomId, $userId, $e->getTraceAsString()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**上传照片配置
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     */
    public function uploadPhotoConf(){

        $roomId = intval(Request::param('roomId'));
        $result = [];
        $giftIds = RoomPhotoService::getInstance()->getRoomPhotoGifts();
        foreach ($giftIds as $giftId) {
            $giftKind = GiftSystem::getInstance()->findGiftKind($giftId);
            if ($giftKind == null) {
                continue;
            }

            $result[] = [
                'giftId' => $giftId,
                'giftImage' => CommonUtil::buildImageUrl($giftKind->image),
                'giftPrice' => $giftKind->price ? $giftKind->price->count : 0,
            ];
        }
        return rjson([
            'roomId' => $roomId,
            'giftList' => $result
        ]);
    }

    /**添加图片操作
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     */
    public function addPhoto()
    {
        //获取数据
        $roomId = intval(Request::param('roomId'));
        $giftId = intval(Request::param('giftId'));
        $image = Request::param('image');
        if (!$roomId || !$giftId || !$image) {
            return rjson([], 500, '参数错误');
        }
        $userId = intval($this->headUid);
        try {
            RoomPhotoService::getInstance()->addPhoto($roomId, $userId, $image, $giftId, time());
            return rjson([], 200, '添加成功');
        } catch (FQException $e) {
            Log::error(sprintf('RoomPhotoController::addPhoto roomId=%d userId=%d giftId=%d image=%s ex=%s',
                $roomId, $userId, $giftId, $image, $e->getTraceAsString()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**移除照片操作
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     */
    public function removePhoto()
    {
        //获取数据
        $roomId = intval(Request::param('roomId'));
        $photoId = intval(Request::param('photoId'));
        if (!$roomId || !$photoId) {
            return rjson([], 500, '参数错误');
        }
        $userId = intval($this->headUid);
        try {
            RoomPhotoService::getInstance()->removePhotos($roomId, $userId, [$photoId]);
            return rjson([], 200, '删除成功');
        } catch (FQException $e) {
            Log::error(sprintf('RoomManagerController::removePhoto roomId=%d userId=%d photoId=%d ex=%s',
                $roomId, $userId, $photoId, $e->getTraceAsString()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**一键移除照片操作
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     */
    public function removeAllPhoto()
    {
        //获取数据
        $roomId = intval(Request::param('roomId'));
        if (!$roomId) {
            return rjson([], 500, '参数错误');
        }
        $userId = intval($this->headUid);
        try {
            RoomPhotoService::getInstance()->removeAllPhoto($roomId, $userId);
            return rjson([], 200, '删除成功');
        } catch (FQException $e) {
            Log::error(sprintf('RoomManagerController::removePhoto roomId=%d userId=%d ex=%s',
                $roomId, $userId, $e->getTraceAsString()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**解锁照片操作
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     */
    public function unlockPhoto()
    {
        //获取数据
        $roomId = intval(Request::param('roomId'));
        $photoId = intval(Request::param('photoId'));
        $micId = intval(Request::param('micId', 0));
        if (!$roomId || !$photoId) {
            return rjson([], 500, '参数错误');
        }
        $userId = intval($this->headUid);
        try {
            RoomPhotoService::getInstance()->unlockPhoto($roomId, $userId, $micId, $photoId);
            return rjson([
                'roomId' => $roomId,
                'photoId' => $photoId
            ], 200, '解锁成功');
        } catch (FQException $e) {
            Log::error(sprintf('RoomManagerController::unlockPhoto roomId=%d userId=%d photoId=%d ex=%s',
                $roomId, $userId, $photoId, $e->getTraceAsString()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**同步公屏操作
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     */
    public function synPublicScreen()
    {
        //获取数据
        $roomId = intval(Request::param('roomId'));
        $photoId = intval(Request::param('photoId'));
        if (!$roomId || !$photoId) {
            return rjson([], 500, '参数错误');
        }
        $userId = intval($this->headUid);
        try {
            RoomPhotoService::getInstance()->synPublicScreen($roomId, $userId, $photoId);
            return rjson([
                'roomId' => $roomId,
                'photoId' => $photoId
            ], 200, '同步成功');
        } catch (FQException $e) {
            Log::error(sprintf('RoomManagerController::unlockPhoto roomId=%d userId=%d photoId=%d ex=%s',
                $roomId, $userId, $photoId, $e->getTraceAsString()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**解锁照片列表
     * @param $token    token值
     * @param $room_id  房间id
     * @param $user_id  用户id
     */
    public function unlockPhotoList()
    {
        //获取数据
        $roomId = intval(Request::param('roomId'));
        $userId = intval($this->headUid);
        try {
            $photoIds = RoomPhotoModelDao::getInstance()->getUnLockPhotoList($roomId, $userId);
            return rjson([
                'roomId' => $roomId,
                'photoIds' => $photoIds
            ]);
        } catch (FQException $e) {
            Log::error(sprintf('RoomManagerController::unlockPhotoList roomId=%d userId=%d ex=%s',
                $roomId, $userId, $e->getTraceAsString()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }
}