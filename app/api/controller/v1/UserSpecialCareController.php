<?php


namespace app\api\controller\v1;


use app\BaseController;
use app\domain\exceptions\FQException;
use app\domain\specialcare\service\UserSpecialCareService;
use app\domain\vip\service\VipService;
use app\facade\RequestAes as Request;
use app\query\user\service\AttentionService;
use think\facade\Log;

/**
 * @desc 特别关心
 * Class UserSpecialCareController
 * @package app\api\controller\v1
 */
class UserSpecialCareController extends BaseController
{
    /**
     * @desc 特别关心列表
     * @return \think\response\Json
     */
    public function getSpecialCareList()
    {
        //获取数据
        $page = Request::param('page', 1, 'intval');
        $pageNum = Request::param('pageNum', 20, 'intval');
        $isPage = Request::param('is_page', 0);  // 是否分页，不分返回所有数据

        $userId = $this->headUid;

        list($total, $data) = UserSpecialCareService::getInstance()->getSpecialCareList($userId, $page, $pageNum, $isPage);

        $pageInfo = array('page' => (int)$page, 'pageNum' => (int)$pageNum, 'totalPage' => ceil($total / $pageNum));
        $result = [
            'list' => $data,
        ];
        if ($isPage){
            $result['pageInfo'] = $pageInfo;
        }
        return rjson($result);
    }

    /**
     * @desc 设置特别关心
     * @return \think\response\Json
     */
    public function setSpecialCare()
    {
        $type = intval(Request::param('type'));  // 1关注 2取关
        $toUserid = Request::param('to_userid');
        if (!$type || !$toUserid) {
            return rjson([], 500, '参数错误');
        }
        $userId = $this->headUid;

        try {
            $isOpenSvip = VipService::getInstance()->isOpenVip($userId, 2);
            if (!$isOpenSvip) {
                throw new FQException('您不是SVIP用户', 500);
            }

            $isFocus = AttentionService::getInstance()->isFocus($userId, $toUserid);
            if (!$isFocus) {
                throw new FQException('请先关注该用户', 500);
            }

            UserSpecialCareService::getInstance()->setSpecialCare($userId, $toUserid, $type);
        } catch (\Exception $e) {
            Log::error(sprintf('AttentionController setSpecialCare Failed userId=%d touserId=%d type=%d errmsg=%d',
                $userId, $toUserid, $type, $e->getTraceAsString()));
            return rjson([], 500, $e->getMessage());
        }

        return rjson();
    }
}