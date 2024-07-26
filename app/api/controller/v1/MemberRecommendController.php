<?php
/**
 * 房间推荐
 * yond
 *
 */

namespace app\api\controller\v1;

use app\api\controller\ApiBaseController;
use app\api\view\v1\MemberView;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\facade\RequestAes as Request;
use app\query\room\service\QueryRoomTypeService;
use app\query\user\cache\CachePrefix;
use app\query\user\cache\IndexUserCache;
use app\query\user\cache\UserModelCache;
use app\service\MemberRecommendService;
use app\utils\CommonUtil;
use Exception;
use think\facade\Log;

class MemberRecommendController extends ApiBaseController
{
    private $coveSexStr = [
        1 => 'man',
        2 => "woman",
        4 => 'all',
    ];

    /**
     * cp匹配
     * @return mixed
     */
    public function recommendUser()
    {
        $versionCheckStatus = Request::middleware('versionCheckStatus',0); //提审状态 1正在提审 0非提审
        try {
            $filterSex = Request::param('filterSex');  //1男 2女
            $userId = $this->headUid;
            if($versionCheckStatus){
                /* app提审中 */
                list($greetTimes, $recommendUserModels) = MemberRecommendService::getInstance()->versionRecommendUser($userId);
            }else{
                list($greetTimes, $recommendUserModels) = MemberRecommendService::getInstance()->recommendUser($userId, $filterSex);
            }
            if ($greetTimes == 0) {
                $code = 503;
                $message = '今日次数已用完，去其他地方看看吧';
            } else {
                $code = 200;
                $message = '返回成功';
            }

            $recommendUserInfo = [];
            $redis = RedisCommon::getInstance()->getRedis();
            $flag = $redis->zScore('user_online_all_list', $userId);
            $online_status = $flag === false ? false : true;
            foreach ($recommendUserModels as $userModel) {
                $recommendUserInfo[] = [
                    'id' => $userModel->userId,
                    'nickname' => $userModel->nickname,
                    'avatar' => CommonUtil::buildImageUrl($userModel->avatar),
                    'city' => $userModel->city ? $userModel->city : '',
                    'onlineStatus' => $online_status,
                ];
            }

            return rjson(['recommendUserInfo' => $recommendUserInfo, 'greetTimes' => $greetTimes], $code, $message);
        } catch (Exception $e) {
            Log::error(sprintf('MemberRecommendController::recommendUser $userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage(). $e->getTraceAsString()));
            return rjson([], 500, '服务器错误');
        }
    }


    /**
     * 首页cp匹配的三张图
     * 产品小姐姐让写的，我的内心是拒绝的
     */
    public function getCpImage()
    {
        //{"boy":["http://mua.com","http://mua.com","http://mua.com"],"girl":["http://mua.com","http://mua.com","http://mua.com"]}
        try {
            $userId = $this->headUid;
            $imageArr = MemberRecommendService::getInstance()->getCpImage($this->headUid);
            return rjson($imageArr);
        } catch (Exception $e) {
            Log::error(sprintf('MemberRecommendController::recommendUser $userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            return rjson([], 500, '服务器错误');
        }
    }

    /**
     * 打招呼
     * @return mixed
     */
    public function greet()
    {
        try {
            $userId = $this->headUid;
            $greetUser = Request::param('greetUser');
            $greetType = Request::param('type'); //1匹配打招呼 2：新用户推荐打招呼
            $greetUserArr = json_decode($greetUser, true);
            MemberRecommendService::getInstance()->greet($userId, $greetUserArr, $greetType);
            return rjson();
        } catch (FQException $e) {
            Log::warning(sprintf('MemberRecommendController::greet $userId=%d ex=%d:%s',
                $userId, $e->getCode(), $e->getMessage()));
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    public function indexListType()
    {
        $versionCheckStatus = Request::middleware('versionCheckStatus'); //提审状态 1正在提审 0非提审
        $userId = $this->headUid;
        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        if ($userModel !== null && $userModel->sex == 2) {
            $data[] =
                [
                    'name' => '在线',
                    'typeId' => 1,
                    'type' => [
                        [
                            'type_id' => '1',
                            'room_mode' => '查看男孩'
                        ],
                        [
                            'type_id' => '2',
                            'room_mode' => '查看女孩'
                        ],
                        [
                            'type_id' => '4',
                            'room_mode' => '随意看看'
                        ],
                    ]
                ];
        } else {
            $data[] =
                [
                    'name' => '在线',
                    'typeId' => 1,
                    'type' => [
                        [
                            'type_id' => '2',
                            'room_mode' => '查看女孩'
                        ],
                        [
                            'type_id' => '1',
                            'room_mode' => '查看男孩'
                        ],
                        [
                            'type_id' => '4',
                            'room_mode' => '随意看看'
                        ],
                    ]
                ];
        }
        //$hot = ['type_id' => 6, 'room_mode' => '热门'];
        if($versionCheckStatus){
            /* app提审中 */
            $list = [
                ['type_id' => 9999, 'room_mode' => '推荐'],
                ['type_id' => 9998, 'room_mode' => '交友'],
                ['type_id' => 9997, 'room_mode' => '闲聊']
            ];
        }else{
            $randLook = ['type_id' => 9999, 'room_mode' => '推荐'];
            $list = QueryRoomTypeService::getInstance()->loadRoomTypeForPidOne();
            array_unshift($list, $randLook);
        }
        $data[] = ['name' => '大厅', 'typeId' => 2, 'type' => $list];
        return rjson($data);
    }

    public function getMuaOnlineUserList()
    {
        $page = Request::param('page') ? Request::param('page') : 1;
        $pageNum = Request::param('pageNum') ? Request::param('pageNum') : 20;
        $type = Request::param('type');
        $filterSex = Request::param('filterSex');
        $userId = $this->headUid;
        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        if (empty($filterSex)) {
            $filterSex = $userModel->sex == 2 ? 1 : 2;  //默认显示女性
        }
        $listUser = MemberRecommendService::getInstance()->getMuaOnlineUserList($filterSex, $page, $pageNum, $type);
        $totalPage = ceil($listUser['count'] / $pageNum);
        $res['allOnlineList'] = $listUser['allOnlineList'];
        $res['pageInfo'] = array("page" => (int)$page, "pageNum" => (int)$pageNum, "totalPage" => $totalPage);
        return rjson($res);
    }

    public function getOnlineList()
    {
        $page = Request::param('page') ? Request::param('page') : 1;
        $pageNum = Request::param('pageNum') ? Request::param('pageNum') : 20;
        $type = Request::param('type');
        $filterSex = Request::param('filterSex');
        $userId = $this->headUid;
        $userModel = UserModelCache::getInstance()->getUserInfo($userId);
        if (empty($filterSex)) {
            $filterSex = $userModel->sex == 2 ? 1 : 2;  //默认显示女性
        }
        $listUser = MemberRecommendService::getInstance()->getMuaOnlineUserList($filterSex, $page, $pageNum, $type);
        return rjson($listUser['allOnlineList']);
    }


    /**
     * @Info 在线用户数据
     * @return \think\response\Json
     */
    public function onlineUser()
    {
        $versionCheckStatus = Request::middleware('versionCheckStatus',0); //提审状态 1正在提审 0非提审
        $page = Request::param('page') ? Request::param('page') : 1;
        $pageNum = Request::param('pageNum') ? Request::param('pageNum') : 20;
        $type = Request::param('type');
        $filterSex = Request::param('filterSex');
        $dataFrom = Request::param('dataFrom') ? Request::param('dataFrom') : 1;//数据来源 1:在线用户列表缓存 2：数据库
        $userId = $this->headUid;
        if ($page == 1) {
            $dataFrom = 1;
        }
        if($versionCheckStatus){
            /* app提审中 */
            $allOnlineList = MemberRecommendService::getInstance()->getVersionOnlineUserList($page,$pageNum);
            $res['allOnlineList'] = $allOnlineList['allOnlineList'];
        }else{
            if ($type == 1) {
                /* app提审中 */
//                $allOnlineList = MemberRecommendService::getInstance()->getVersionOnlineUserList($page,$pageNum);
//                $res['allOnlineList'] = $allOnlineList['allOnlineList'];
                $allOnlineList = $this->fitOnlineUser($userId, $filterSex, $page, $pageNum, $this->source);
                $res['allOnlineList'] = $allOnlineList;
            } else {
                $allOnlineList = MemberRecommendService::getInstance()->getMuaOnlineUserList($filterSex, $page, $pageNum, $type);
                $res['allOnlineList'] = $allOnlineList['allOnlineList'];
            }
        }
        $res['dataFrom'] = $dataFrom;
        $res['pageInfo'] = array("page" => (int)$page, "pageNum" => (int)$pageNum, "totalPage" => 10);
        return rjson($res);
    }

    private function fitOnlineUser($userId, $filterSex, $page, $pageNum, $source)
    {
        $sex = $this->coveSexStr[$filterSex] ?? "woman";
        list($userData, $offlineUid) = $this->getOnlineMemberUserList($page, $pageNum, $sex, $userId);
        return $this->coveOnlineUserView($userData, $offlineUid);
    }

    private function getOnlineMemberUserList($page, $pageNum, $sex, $userId)
    {
//        初始化用户缓存桶数据,检查当前是否存在桶，如果存在return
        $indexCache = new IndexUserCache($page, $pageNum, $sex, $userId);
//        初始化
        $indexCache->initPrivateUserBucket($userId);
//        取数据
        $uids = $indexCache->getOnlinePrivateUser();
        if (empty($uids)) {
            return [[], 0];
        }
        shuffle($uids);
        //初始化用户数据
        $userData = UserModelCache::getInstance()->findList($uids);

        if (empty($userData)) {
            return [[], 0];
        }
        //获取不在线用户id
        $offlineUid = $this->recverOfflineBucket($userData);
        //将不在线用户则存入到离线用户桶
        $indexCache->rpushOfflineBucket($offlineUid);
        //过滤取过的不在线人数，超过10人,剩余不足一屏，则reset用户桶
        $indexCache->filterOfflineUserNum($pageNum);
        return [$userData, $offlineUid];
    }

    private function recverOfflineBucket($userModels)
    {
        $redis = RedisCommon::getInstance()->getRedis();
        $offlineUid = [];
        foreach ($userModels as $userModel) {
            $cacheKey = sprintf(CachePrefix::$USER_ONLINE_SEX_CACHE, $userModel->sex);
            if(empty($redis->zScore($cacheKey, $userModel->userId))){
                $offlineUid[] = $userModel->userId;
            }
        }
        return $offlineUid;
    }

    public function coveOnlineUserView($userModels, $offlineUid)
    {
        $allOnlineList = [];
        foreach ($userModels as $userModel) {
            #在线为1 不在线为2
            $onlineStatus = in_array($userModel->userId, $offlineUid) ? 2 : 1;
            $allOnlineList[] = MemberView::onlineUserView($userModel, $onlineStatus);
        }
        return $allOnlineList;
    }

}