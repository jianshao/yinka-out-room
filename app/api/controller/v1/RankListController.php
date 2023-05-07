<?php
/*
 * 房间财富与心动排行榜
 */

namespace app\api\controller\v1;

use app\api\view\v1\RankListView;
use app\domain\exceptions\FQException;
use app\domain\rank\service\RankService;
use app\query\user\cache\UserModelCache;
use app\service\CommonCacheService;
use \app\facade\RequestAes as Request;
use app\utils\ArrayUtil;
use think\facade\Log;
use app\api\controller\ApiBaseController;


class RankListController extends ApiBaseController
{

    /**房间排行榜数据
     * @param $token    token值
     * @param $room_id  房间id
     * @param $type 1代表财富 2代表心动
     * @param $status 1日榜 2周榜 3月榜
     */
    public function getList()
    {
        //获取数据
        try {
            $versionCheckStatus = Request::middleware('versionCheckStatus'); //提审状态 1正在提审 0非提审
            $roomId = Request::param('room_id');
            $type = Request::param('type');
            $status = Request::param('status');
            if (!is_numeric($roomId) || !is_numeric($type) || !is_numeric($status)) {
                return rjson([], 500, '参数错误');
            }
            $rankList = [];
            $selfList = [];
            if($versionCheckStatus){
                /* app提审中 */
                $rankData = RankService::getInstance()->getVersionRankData($type, $roomId, $status);
            }else{
                $rankData = RankService::getInstance()->getRankData($type, $roomId, $status);
            }
            $length = 20;
            if ($roomId == 0) {
                $length = 50;
            }
            $rankData = array_slice($rankData, 0, $length, true);

            $userIds = [];
            foreach ($rankData as $userId => $value) {
                $userIds[] = $userId;
            }
            $userModelMap = UserModelCache::getInstance()->findUserModelMapByUserIds($userIds);

            $randId = 1;
            foreach ($rankData as $userId => $value) {
                $userRoomId = CommonCacheService::getInstance()->getUserCurrentRoom($userId);
                $userModel = ArrayUtil::safeGet($userModelMap, $userId);
                if (empty($userModel)){
                    continue;
                }
                $data = RankListView::encodeRankData($userModel, $value, $userRoomId);
                $data['number'] = $randId++;
                $rankList[] = $data;

                if ($this->headUid == $userId) {
                    $selfList = $data;
                }
            }

            //根据用户id获取用户基本信息
            if (empty($selfList)) {
                $userRoomId = CommonCacheService::getInstance()->getUserCurrentRoom($this->headUid);
                $userModel = UserModelCache::getInstance()->getUserInfo($this->headUid);
                $selfList = RankListView::encodeRankData($userModel, '--', $userRoomId);
                $selfList['number'] = '未上榜';
            }

            $result = [
                "rank_list" => $rankList,
                "self_list" => $selfList
            ];
            return rjson($result);
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 粉丝贡献榜
     */
    public function fansRankList()
    {
        //获取数据
        try {
            $status = Request::param('status', 1);
            if (!is_numeric($status)) {
                return rjson([], 500, '参数错误');
            }

            $rankList = [];
            $rankData = RankService::getInstance()->getFansRankData(3, $this->headUid, $status);

            $userIds = [];
            foreach ($rankData as $userId => $value) {
                $userIds[] = $userId;
            }

            $userModelMap = UserModelCache::getInstance()->findUserModelMapByUserIds($userIds);

            $randId = 1;
            foreach ($rankData as $userId => $value) {
                $userRoomId = CommonCacheService::getInstance()->getUserCurrentRoom($userId);
                $userModel = ArrayUtil::safeGet($userModelMap, $userId);
                if (empty($userModel)){
                    continue;
                }
                $data = RankListView::encodeRankData($userModel, $value, $userRoomId);
                $data['number'] = $randId++;
                $rankList[] = $data;
            }
            return rjson($rankList);
        } catch (FQException $e) {
            Log::record($e->getCode() . '---' . $e->getMessage());
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }


    /**
     * @info 首页榜单小标icon
     * @return \think\response\Json
     * @throws FQException
     */
    public function indexIcon()
    {
        $roomId = 0;
        $versionCheckStatus = Request::middleware('versionCheckStatus'); //提审状态 1正在提审 0非提审
        $pageNum = Request::param('pageNum', 3, 'intval');
        $rankList = [];
        if($versionCheckStatus){
            $rankData = RankService::getInstance()->getVersionRankData(2, $roomId, 1, 2);
        }else{
            $rankData = RankService::getInstance()->getRankData(2, $roomId, 1, $pageNum);
        }
        if (empty($rankData)) {
            return rjson(['rank_list' => []], 200, 'success');
        }
        $rankData = array_slice($rankData, 0, $pageNum, true);
        $userIds = [];
        foreach ($rankData as $userId => $value) {
            $userIds[] = $userId;
        }
        $userModelMap = UserModelCache::getInstance()->findUserModelMapByUserIds($userIds);
        foreach ($rankData as $userId => $value) {
            $userRoomId = CommonCacheService::getInstance()->getUserCurrentRoom($userId);
            $userModel = ArrayUtil::safeGet($userModelMap, $userId);
            if (empty($userModel)){
                continue;
            }
            $data = RankListView::encodeRankIconData($userModel, $value, $userRoomId);
            $rankList[] = $data;
        }
        return rjson(['rank_list' => $rankList], 200, 'success');
    }

}