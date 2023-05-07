<?php

namespace app\api\controller\v1\Activity;

use app\BaseController;
use app\common\RedisCommon;
use app\domain\activity\acrosspk\AcrossPKService;
use app\domain\exceptions\FQException;
use app\domain\room\dao\RoomModelDao;
use app\query\user\cache\UserModelCache;
use app\utils\CommonUtil;

class AcrossPKController extends BaseController
{
    private function checkMToken() {
        $token = $this->request->param('mtoken');
        $redis = RedisCommon::getInstance()->getRedis();
        if (!$token) {
            throw new FQException('用户信息错误', 500);
        }
        $userId = $redis->get($token);
        if (!$userId) {
            throw new FQException('用户信息错误', 500);
        }
        return $userId;
    }

    public function acrossPKData()
    {
        $userId = $this->checkMToken();
        $pkList = [];
        $redis = RedisCommon::getInstance()->getRedis();
        foreach (AcrossPKService::$rankMap as $key => $rank){
            $pkList[$rank] = [];
            $matchInfo = $redis->hGet(AcrossPKService::$acrossKey, AcrossPKService::getInstance()->matchKey($rank));
            if (empty($matchInfo))continue;

            $matchInfo = json_decode($matchInfo, true);
            foreach ($matchInfo as $key=>$info){
                $createRoomId = $info['createRoomId'];
                $pkRoomId = $info['pkRoomId'];
                $roomMap = RoomModelDao::getInstance()->findRoomModelsMap([$createRoomId, $pkRoomId]);
                $pkList[$rank][] = [
                    'createRoomId' => $createRoomId,
                    'createPrettyRoomId' => $roomMap[$createRoomId]->prettyRoomId,
                    'createRoomName' => $roomMap[$createRoomId]->name,
                    'createRoomImage' => CommonUtil::buildImageUrl(UserModelCache::getInstance()->findAvatarByUserId($roomMap[$createRoomId]->userId)),
                    'pkRoomId' => $pkRoomId,
                    'pkPrettyRoomId' => $roomMap[$pkRoomId]->prettyRoomId,
                    'pkRoomName' => $roomMap[$pkRoomId]->name,
                    'pkRoomImage' => CommonUtil::buildImageUrl(UserModelCache::getInstance()->findAvatarByUserId($roomMap[$pkRoomId]->userId)),
                    'winRoomId' => $info['winRoomId'],
                ];
            }
        }

        $champion = null;
        $roomId = $redis->hGet(AcrossPKService::$acrossKey, 1);
        if (!empty($roomId)){
            $roomId = json_decode($roomId, true);
            $roomId = $roomId[0];
            $roomModel = RoomModelDao::getInstance()->loadRoom($roomId);
            if (!empty($roomModel)){
                $champion = [
                    'roomId' => $roomId,
                    'prettyRoomId' => $roomModel->prettyRoomId,
                    'roomName' => $roomModel->name,
                    'roomImage' => CommonUtil::buildImageUrl(UserModelCache::getInstance()->findAvatarByUserId($roomModel->userId)),
                ];
            }
        }

        return rjsonFit([
            'userId' => $userId,
            'champion' => $champion,
            'pkList' => $pkList
        ]);
    }
}