<?php

namespace app\api\controller\inner;

use app\Base2Controller;
use app\common\RedisCommon;
use app\domain\exceptions\FQException;
use app\domain\forum\service\ForumService;
use think\facade\Request;

class ForumController extends Base2Controller
{

    /**
     * 审核动态
     * @param int  $type     1通过 2拒绝
     * @param int  $forumId  动态ID
     * @return \think\response\Json
     */
    public function checkForum()
    {
        $this->checkAuthInner();
        $type = intval(Request::param('type'));
        $forumId = intval(Request::param('forumId'));
        if(!$type || !$forumId){
            return rjson([],500,'参数有误');
        }
        try {
            ForumService::getInstance()->checkForum($type,$forumId);
            return rjson([],200,'审核完成');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

    /**
     * 审核动态
     * @param int  $forumId  动态ID
     * @return \think\response\Json
     */
    public function delForum()
    {
        $adminUid = $this->checkAuthInner();
        $forumId = intval(Request::param('forumId'));
        try {
            ForumService::getInstance()->adminDelForum($forumId,$adminUid);
            return rjson([],200,'删除成功');
        } catch (FQException $e) {
            return rjson([], $e->getCode(), $e->getMessage());
        }
    }

}