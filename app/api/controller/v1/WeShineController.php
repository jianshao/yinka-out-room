<?php

namespace app\api\controller\v1;

use app\api\controller\ApiBaseController;
use app\domain\exceptions\FQException;
use app\domain\user\service\UserService;
use app\facade\RequestAes as Request;
use app\query\user\cache\UserModelCache;
use app\query\weshine\model\WeShineModel;
use app\query\weshine\service\WeShineService;

class WeShineController extends ApiBaseController
{

    /**
     * Notes: 闪萌搜索接口
     */
    public function shineSearch()
    {
        $keyword = Request::param('keyword', '', 'trim');
        $offset = Request::param('offset', 0, 'intval');
        $limit = Request::param('limit', 20, 'intval');
        if (empty($keyword)) {
            return rjson([], 500, '参数错误');
        }
        if ($limit > 50) {
            $limit = 50;
        }
        list($lists, $pageInfo) = WeShineService::getInstance()->shineSearch($keyword, $offset, $limit);
        $data = [
            'list' => $lists,
            'pageInfo' => $pageInfo
        ];
        return rjson($data, 200, '返回成功');
    }

    /**
     * Notes: 闪萌热门表情 (最近使用)
     */
    public function shineHotLook()
    {
        $offset = Request::param('offset', 0, 'intval');
        $limit = Request::param('limit', 20, 'intval');
        list($lists, $pageInfo) = WeShineService::getInstance()->getUserShineHotLookListForCache($this->headUid, $offset, $limit);
        $userHistory = WeShineService::getInstance()->getHistoryShineForUser($this->headUid);
        $data = [
            'history' => $userHistory,
            'list' => $lists,
            'pageInfo' => $pageInfo
        ];
        return rjson($data, 200, '返回成功');
    }

    /**
     * @info 最近使用表情
     * @return \think\response\Json
     * @throws FQException
     */
    public function getHistoryShine()
    {
        $lists = WeShineService::getInstance()->getHistoryShineForUser($this->headUid);
        return rjson(['list' => $lists], 200, 'success');
    }

    /**
     * @return \think\response\Json
     * @throws FQException
     */
    public function setHistoryShine()
    {
        $shine = Request::param('shine', '');
        $width = Request::param("width", 0, 'intval');
        $height = Request::param("height", 0, 'intval');
        $WeShineModel = new WeShineModel;
        $WeShineModel->src = $shine;
        $WeShineModel->width = $width;
        $WeShineModel->height = $height;
        $result = WeShineService::getInstance()->setHistoryShineForUser($this->headUid, $WeShineModel);
        return rjson(['status' => $result], 200, 'success');
    }

    /**
     * @info 闪萌打招呼表情
     * @return \think\response\Json
     */
    public function shineHi()
    {
        $lists = WeShineService::getInstance()->getShineHi();
        $data = [
            'list' => $lists,
        ];
        return rjson($data, 200, 'success');
    }

    /**
     * @info 是否存在成功聊天 1 存在聊天 2 没聊过天
     * @return \think\response\Json
     * @throws FQException
     */
    public function userTalkStatus()
    {
        $toUid = Request::param('toUid', 0, 'intval');
        if (empty($toUid)) {
            throw new FQException("参数错误", 500);
        }
        $status = WeShineService::getInstance()->userTalkStatus($this->headUid, $toUid);

        $userModel = UserModelCache::getInstance()->getUserInfo($toUid);
        $accountState = $userModel ? UserService::getInstance()->getUserStatus($userModel) : 0;
        return rjson([
            'status' => $status,
            'accountState' => $accountState
        ], 200, 'success');
    }
}